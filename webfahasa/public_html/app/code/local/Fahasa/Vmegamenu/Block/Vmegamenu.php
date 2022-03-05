<?php
/**
 * Override Vmegamenu to only display the first level of category
 */
class Fahasa_Vmegamenu_Block_Vmegamenu extends Magentothem_Vmegamenu_Block_Vmegamenu{    

    public function drawCustomMenuItem($category, $level = 0, $last = false, $class='',$i)
    {
        if (!$category->getIsActive()) return '';

        $html = array();
        $blockHtml = '';
        $id = $category->getId();
        // --- Static Block ---
        $blockId = sprintf('pt_menu_idcat_%d', $id); // --- static block key
        $blockHtml = $this->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
        /*check block right*/
        $blockIdRight = sprintf('pt_menu_idcat_%d_right', $id); // --- static block key
        $blockHtmlRight = $this->getLayout()->createBlock('cms/block')->setBlockId($blockIdRight)->toHtml();
        if($blockHtmlRight) $blockHtml = $blockHtmlRight;
        
        $rightmenu = Mage::getStoreConfig('vmegamenu/general/rightmenu');
        // --- Sub Categories ---
        $activeChildren = $this->getActiveChildren($category, $level);
        // --- class for active category ---
        $active = ''; if ($this->isCategoryActive($category)) $active = ' act';
        // --- Popup functions for show ---
        $drawPopup = ($blockHtml || count($activeChildren));
		$item_show = Mage::getStoreConfig('vmegamenu/general/item_show');
        if ($drawPopup)
        {  
			if($i <= $item_show)
				$html[] = '<div id="pt_menu' . $id . '" class="pt_menu' . $active . ' '.$class.' " >';
			else
				$html[] = '<div id="pt_menu' . $id . '" class="extra_menu pt_menu' . $active . ' '.$class.' " >';
        }
        else
        {
			if($i <= $item_show)
				$html[] = '<div id="pt_menu' . $id . '" class="pt_menu' . $active .' '.$class.'">';
			else
				$html[] = '<div id="pt_menu' . $id . '" class="extra_menu pt_menu' . $active .' '.$class.'">';
        }
		$hasSubMenu = '';
		if (!$drawPopup){ $hasSubMenu = ' noSubMenu'; } else { $hasSubMenu = ''; }
        // --- Top Menu Item ---
        $html[] = '<div class="parentMenu'.$hasSubMenu.'">';
        $html[] = '<a href="'.$this->getCategoryUrl($category).'">';
        $name = $this->escapeHtml($category->getName());
        $name = str_replace(' ', '&nbsp;', $name);
		$thumbnail = Mage::getModel('catalog/category')->load($category->getId())->getThumbnail();
		if($thumbnail) {
			//$thumbnail_url = Mage::getBaseUrl('media').'catalog/category/'.$thumbnail;
			$thumbnail_url_resize = $this->getResizedImage(16,16,100,$thumbnail);
			 $html[] = '<span><img src="'.$thumbnail_url_resize.'"  alt= "'.$category->getName().'"/></span><span>' . $name . '</span>';
		} else {
			$html[] = '<span>' . $name . '</span>';
		}
        $html[] = '</a>';
        $html[] = '</div>';
                
        
        $html[] = '</div>';
        $html = implode("\n", $html);
        return $html;
    }    
	
}