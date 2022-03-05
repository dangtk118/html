<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Sphinx Search Ultimate
 * @version   2.3.2
 * @revision  886
 * @copyright Copyright (C) 2014 Mirasvit (http://mirasvit.com/)
 */


abstract class Mirasvit_SearchIndex_Model_Engine
{
    abstract public function query($queryText, $store, $index);

    protected function _normalize($input)
    {
        if (!count($input)) {
            return $input;
        }
        
        arsort($input);

        // We segment the result with each group size is at least 5.
        $result = array();
        $max    = max(array_values($input));
        $max    = $max > 0 ? $max : 1;
        $count = 0;
        //We have too many items, so we use group size of 50.
        $groupSize = 50;
        $groupMin = 3;
        
        if (count($input) < $groupMin * $groupSize) {
            // We have few items, so we segment into less group.
            $groupSize = round(count($input) / $groupMin, 0);
            if ($groupSize < 2) {
                $groupSize = 2;
            }
        }
        
        $lastVal = $groupSize;
        $lastRawVal = $lastVal;        
        foreach ($input as $key => $value) {
            $curVal = round($value / $max * $groupSize);
            // We want each group has at least 7 items, so
            // we may let lessly matched items to join the better match group.
            if ($count < $groupMin || $curVal == $lastRawVal) {            
                $curVal = $lastVal;
            }
            else {
                $count = 0;
            }
            $count++;
                            
            $result[$key] = $curVal;
            $lastVal = $curVal;            
            $lastRawVal = round($value / $max * $groupSize);
        }

        return $result;
    }

    protected function _getReadAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }
}
