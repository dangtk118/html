<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Adminhtml_Customer_Renderer_City implements Varien_Data_Form_Element_Renderer_Interface {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        } 
        
	static protected $_cityCollections;

	public function render(Varien_Data_Form_Element_Abstract $element) {
		$html = '<tr>'."\n";

		$countryId = false;
		if ($country = $element->getForm()->getElement('country_id')) {
			$countryId = $country->getValue();
		}
		
		$regionId = intval($element->getForm()->getElement('region_id')->getValue());		
		$region = intval($element->getForm()->getElement('region')->getValue());		

		$cityCollection = false;
		if ($countryId) {
			if (!isset(self::$_cityCollections[$regionId])) {
				$collection = Mage::getModel($this->getModuleStr() . '/district')
					->getCollection();
				$collection->getSelect()
					->join(array('dr' => Mage::getSingleton('core/resource')->getTableName('directory_country_region')), 'main_table.`province_id`=dr.`province_id`')
					->where('main_table.status=?', 1)
					->where('dr.region_id=?', $regionId);
				if (count($collection)) {
					foreach ($collection as $item) {
						$_city = array(
							'title' => $item->getDistrictName(),
							'label' => $item->getDistrictName(),
							'value' => $item->getDistrictName(),
						);
						self::$_cityCollections[$regionId][] = $_city;
					}									
				}				
					
			}			
			$cityCollection = self::$_cityCollections[$regionId];
			
		}
		
		$htmlAttributes = $element->getHtmlAttributes();
		foreach ($htmlAttributes as $key => $attribute) {
			if ('type' === $attribute) {
				unset($htmlAttributes[$key]);
				break;
			}
		}

		// Output two elements - for 'city' and for 'city_id'.
		// Two elements are needed later upon form post - to properly set data to address model,
		// otherwise old value can be left in region_id attribute and saved to DB.
		// Depending on country selected either 'region' (input text) or 'region_id' (selectbox) is visible to user
		$cityHtmlName = $element->getName();
		$cityIdHtmlName = str_replace('city', 'city_id', $cityHtmlName);
		$cityHtmlId = $element->getHtmlId();
		$cityIdHtmlId = str_replace('city', 'city_id', $cityHtmlId);
		
		$cityId = $element->getForm()->getElement('city')->getValue();

		if ($cityCollection && count($cityCollection) > 0) {
			$elementClass = $element->getClass();			
			$elementClass .= " cities";
			$html.= '<td class="label">'.$element->getLabelHtml().'</td>';
			$html.= '<td class="value">';

			$html .= '<select id="' . $cityIdHtmlId . '" name="' . $cityIdHtmlName . '" class="cities input-text required-entry">' . "\n";
			foreach ($cityCollection as $city) {				
				$selected = ($cityId==$city['value']) ? ' selected="selected"' : '';
				$html.= '<option value="' . $city['value'] . '"' . $selected . '>'
					. $city['label']
					. '</option>';
			}
			$html.= '</select>' . "\n";

			$html .= '<input type="hidden" name="' . $cityHtmlName . '" id="' . $cityHtmlId . '" value="'.$cityId.'"/>';

			$html.= '</td>';
			$element->setClass($elementClass);
		} else {
			$element->setClass('input-text required-entry');
			$html.= '<td class="label"><label for="'.$element->getHtmlId().'">'
				. $element->getLabel()
				. ' <span class="required">*</span></label></td>';

			$element->setRequired(true);
			$html.= '<td class="value">';
			$html .= '<input id="' . $cityHtmlId . '" name="' . $cityHtmlName
				. '" value="' . $element->getEscapedValue() . '" '
				. $element->serialize($htmlAttributes) . "/>" . "\n";
			$html .= '<input type="hidden" name="' . $cityIdHtmlName . '" id="' . $cityIdHtmlId . '" value=""/>';
			$html .= '</td>'."\n";
		}
		$html.= '</tr>'."\n";
		return $html;
	}
}