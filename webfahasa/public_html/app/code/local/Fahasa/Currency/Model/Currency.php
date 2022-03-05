<?php
/**
 * <pre>
 * Currency class that override Mage_Directory_Model_Currency to:
 * 1. Override precision
 * 2. Change currency position to vn locale without touching lib/Zend/Locale/Data/**.xml
 * </pre>
 */
class Fahasa_Currency_Model_Currency extends Mage_Directory_Model_Currency{
    /**
     * Format price to currency format. No precision, and add format to display 
     * currency symbol at the end.
     *
     * @param float $price
     * @param array $options
     * @param bool $includeContainer
     * @param bool $addBrackets
     * @return string
     */
    public function format($price, $options = array(), $includeContainer = true, $addBrackets = false)
    {
        $options['format'] = "#,##0.00 ¤";
        $options['locale'] = "vi_VN";
        return $this->formatPrecision($price, 0, $options, $includeContainer, $addBrackets);
    }
}

