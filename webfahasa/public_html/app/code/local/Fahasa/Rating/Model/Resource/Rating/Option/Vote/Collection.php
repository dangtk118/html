<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Rating
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Rating votes collection
 *
 * @category    Mage
 * @package     Mage_Rating
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Fahasa_Rating_Model_Resource_Rating_Option_Vote_Collection extends Mage_Rating_Model_Resource_Rating_Option_Vote_Collection
{
    
    /**
     * Set store filter
     *
     * @param int $storeId
     * @return Mage_Rating_Model_Resource_Rating_Option_Vote_Collection
     */
    public function setStoreFilter($storeId)
    {
//        $this->getSelect()
//            ->join(array('rstore'=>$this->getTable('review/review_store')),
//                $this->getConnection()->quoteInto(
//                    'main_table.review_id=rstore.review_id AND rstore.store_id=?',
//                    (int)$storeId),
//            array());
        //Override core to make rating appear for every store
        return $this;
    }    
    public function addRatingInfo($storeId=null)
    {
        $adapter=$this->getConnection();
        $ratingCodeCond = $adapter->getIfNullSql('title.value', 'rating.rating_code');
        $this->getSelect()
            ->join(
                array('rating'    => $this->getTable('rating/rating')),
                'rating.rating_id = main_table.rating_id',
                array('rating_code'))
            ->joinLeft(
                array('title' => $this->getTable('rating/rating_title')),
                $adapter->quoteInto('main_table.rating_id=title.rating_id', ''),
                array('rating_code' => $ratingCodeCond));
        $adapter->fetchAll($this->getSelect());
        return $this;
    }
}
