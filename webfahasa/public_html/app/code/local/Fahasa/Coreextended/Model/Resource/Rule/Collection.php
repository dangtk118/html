<?php

class Fahasa_Coreextended_Model_Resource_Rule_Collection extends Mage_SalesRule_Model_Resource_Rule_Collection
{

    /**
     * Filter collection by specified website, customer group, coupon code, date.
     * Filter collection to use only active rules.
     * Involved sorting by sort_order column.
     *
     * Overwrite the parent for better performance.
     *
     * We UNION where noCoupon and yesCoupon SELECTs rather than use an OR
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @use $this->addWebsiteGroupDateFilter()
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function setValidationFilter($websiteId, $customerGroupId, $couponCode = '', $now = null)
    {
        if (!$this->getFlag('validation_filter')) {
            parent::setValidationFilter($websiteId, $customerGroupId, $couponCode, $now);

            if (!strlen($couponCode)) {
                return $this;
            }

            $connection = $this->getConnection();

            $noCoupon = $this->baseSelectForUnion();
            $noCoupon->where($connection->quoteInto(
                'main_table.coupon_type = ? ',
                Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON
            ));

            $yesCoupon = $this->baseSelectForUnion();
            $yesCoupon->where($this->yesCouponWhere(), $couponCode);

            $subselect = $connection->select()->union(array(
                $noCoupon,
                $yesCoupon,
            ));

            $this->getSelect()->reset();
            $this->getSelect()->from(array('main_table' => $subselect));
        }

        return $this;
    }

    /**
     * Get a select to use for the UNION
     *
     * Discards the last applied WHERE condition (they will be applied
     * separately and UNION-ed)
     *
     * @return Varien_Db_Select
     */
    protected function baseSelectForUnion()
    {
        $select = clone $this->getSelect();
        $where = $select->getPart('where');
        array_pop($where);
        $select->setPart('where', $where);

        return $select;
    }

    /**
     * Get the WHERE for the yes coupon SELECT.
     *
     * This logic can be found in setValidationFilter() in the parent.
     *
     * @return string
     */
    protected function yesCouponWhere()
    {
        $connection = $this->getConnection();

        $orWhereConditions = array(
            $connection->quoteInto(
                '(main_table.coupon_type = ? AND rule_coupons.type = 0)',
                Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO
            ),
            $connection->quoteInto(
                '(main_table.coupon_type = ? AND main_table.use_auto_generation = 1 AND rule_coupons.type = 1)',
                Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
            ),
            $connection->quoteInto(
                '(main_table.coupon_type = ? AND main_table.use_auto_generation = 0 AND rule_coupons.type = 0)',
                Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
            ),
        );
        $orWhereCondition = implode(' OR ', $orWhereConditions);

        return '(' . $orWhereCondition . ') AND rule_coupons.code = ?';
    }
}
