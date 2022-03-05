<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Navigation
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-flickr"></i>'.Mage::helper('revslider')->__('Navigation');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Navigation Settings');
    }

    public function canShowTab(){
        return true;
    }

    public function isHidden(){
        return false;
    }

    public function _prepareForm(){
        /* @var $model AM_RevSlider_Model_Slider */
        $model = Mage::registry('revslider');
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('navigation_fieldset', array(
            'legend' => Mage::helper('revslider')->__('Navigation Settings')
        ));
        $fieldset2 = $form->addFieldset('bullet_fieldset', array(
            'legend' => Mage::helper('revslider')->__('Bullets / Thumbnails Position')
        ));
        $fieldset3 = $form->addFieldset('arrow_fieldset', array(
            'legend' => Mage::helper('revslider')->__('Arrows Position')
        ));

        $fieldset->addField('stop_on_hover', 'select', array(
            'name'      => 'stop_on_hover',
            'label'     => Mage::helper('revslider')->__('Stop On Hover'),
            'title'     => Mage::helper('revslider')->__('Stop On Hover'),
            'values'    => $model->getOnOffOptions(),
            'value'     => $model->getData('stop_on_hover') ? $model->getData('stop_on_hover') : 'on',
            'note'      => Mage::helper('revslider')->__('Stop the Timer when hovering the slider')
        ));
        $fieldset->addField('keyboard_navigation', 'select', array(
            'name'      => 'keyboard_navigation',
            'label'     => Mage::helper('revslider')->__('Keyboard Navigation'),
            'title'     => Mage::helper('revslider')->__('Keyboard Navigation'),
            'values'    => $model->getOnOffOptions(),
            'value'     => $model->getData('keyboard_navigation') ? $model->getData('keyboard_navigation') : 'on',
            'note'      => Mage::helper('revslider')->__('Allow/disallow to navigate the slider with keyboard')
        ));
        $show = $fieldset->addField('navigaion_always_on', 'select', array(
            'name'      => 'navigaion_always_on',
            'label'     => Mage::helper('revslider')->__('Always Show Navigation'),
            'title'     => Mage::helper('revslider')->__('Always Show Navigation'),
            'values'    => $model->getYesNoOptions(),
            'note'      => Mage::helper('revslider')->__('Always show the navigation and the thumbnails')
        ));
        $show1 = $fieldset->addField('hide_thumbs', 'text', array(
            'name'      => 'hide_thumbs',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Hide Navitagion After'),
            'title'     => Mage::helper('revslider')->__('Hide Navitagion After'),
            'value'     => $model->getData('hide_thumbs') ? $model->getData('hide_thumbs') : 200,
            'note'      => Mage::helper('revslider')->__('Time after that the Navigation and the Thumbs will be hidden (Default: 200 ms)')
        ));

        $nav = $fieldset3->addField('navigaion_type', 'select', array(
            'name'      => 'navigaion_type',
            'label'     => Mage::helper('revslider')->__('Bullet Type'),
            'title'     => Mage::helper('revslider')->__('Bullet Type'),
            'values'    => $model->getNavigationTypeOptions(),
            'value'     => $model->getData('navigaion_type') ? $model->getData('navigaion_type') : 'bullet',
            'note'      => Mage::helper('revslider')->__('Display type of the navigation bar (Default: None)')
        ));
        $nav11 = $fieldset3->addField('navigaion_align_hor', 'select', array(
            'name'      => 'navigaion_align_hor',
            'label'     => Mage::helper('revslider')->__('Navigation Horizontal Align'),
            'title'     => Mage::helper('revslider')->__('Navigation Horizontal Align'),
            'values'    => $model->getLCROptions(),
            'value'     => $model->getData('navigaion_align_hor') ? $model->getData('navigaion_align_hor') : 'center',
            'note'      => Mage::helper('revslider')->__('Horizontal Align of Bullets / Thumbnails')
        ));
        $nav12 = $fieldset3->addField('navigaion_align_vert', 'select', array(
            'name'      => 'navigaion_align_vert',
            'label'     => Mage::helper('revslider')->__('Navigation Vertical Align'),
            'title'     => Mage::helper('revslider')->__('Navigation Vertical Align'),
            'values'    => $model->getTCBOptions(),
            'value'     => $model->getData('navigaion_align_vert') ? $model->getData('navigaion_align_vert') : 'bottom',
            'note'      => Mage::helper('revslider')->__('Vertical Align of Bullets / Thumbnails')
        ));
        $nav13 = $fieldset3->addField('navigaion_offset_hor', 'text', array(
            'name'      => 'navigaion_offset_hor',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Navigation Horizontal Offset'),
            'title'     => Mage::helper('revslider')->__('Navigation Horizontal Offset'),
            'value'     => $model->getData('navigaion_offset_hor') ? $model->getData('navigaion_offset_hor') : 0,
            'note'      => Mage::helper('revslider')->__('Offset from current Horizontal position of Bullets / Thumbnails negative and positive direction')
        ));
        $nav14 = $fieldset3->addField('navigaion_offset_vert', 'text', array(
            'name'      => 'navigaion_offset_vert',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Navigation Vertical Offset'),
            'title'     => Mage::helper('revslider')->__('Navigation Vertical Offset'),
            'value'     => $model->getData('navigaion_offset_vert') ? $model->getData('navigaion_offset_vert') : 20,
            'note'      => Mage::helper('revslider')->__('Offset from current Vertical  position of Bullets / Thumbnails negative and positive direction')
        ));

        $arrow = $fieldset2->addField('navigation_style', 'select', array(
            'name'      => 'navigation_style',
            'label'     => Mage::helper('revslider')->__('Navigation Style'),
            'title'     => Mage::helper('revslider')->__('Navigation Style'),
            'values'    => $model->getNavigationStyleOptions(),
            'note'      => Mage::helper('revslider')->__('Look of the navigation bullets. If you choose navbar, we recommend to choose Navigation Arrows to With Bullets')
        ));
        $arrow11 = $fieldset2->addField('leftarrow_align_hor', 'select', array(
            'name'      => 'leftarrow_align_hor',
            'label'     => Mage::helper('revslider')->__('Left Arrow Horizontal Align'),
            'title'     => Mage::helper('revslider')->__('Left Arrow Horizontal Align'),
            'values'    => $model->getLCROptions(),
            'value'     => $model->getData('leftarrow_align_hor') ? $model->getData('leftarrow_align_hor') : 'left',
            'note'      => Mage::helper('revslider')->__('Vertical Align of left Arrow (only if arrow is not next to bullets)')
        ));
        $arrow12 = $fieldset2->addField('leftarrow_align_vert', 'select', array(
            'name'      => 'leftarrow_align_vert',
            'label'     => Mage::helper('revslider')->__('Left Arrow Vertical Offset'),
            'title'     => Mage::helper('revslider')->__('Left Arrow Vertical Offset'),
            'values'    => $model->getTCBOptions(),
            'value'     => $model->getData('leftarrow_align_vert') ? $model->getData('leftarrow_align_vert') : 'center',
            'note'      => Mage::helper('revslider')->__('Vertical Align of left Arrow (only if arrow is not next to bullets)')
        ));
        $arrow13 = $fieldset2->addField('leftarrow_offset_hor', 'text', array(
            'name'      => 'leftarrow_offset_hor',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Left Arrow Horizontal Offset'),
            'title'     => Mage::helper('revslider')->__('Left Arrow Horizontal Offset'),
            'value'     => $model->getData('leftarrow_offset_hor') ? $model->getData('leftarrow_offset_hor') : 20,
            'note'      => Mage::helper('revslider')->__('Offset from current Horizontal position of Bullets / Thumbnails negative and positive direction')
        ));
        $arrow14 = $fieldset2->addField('leftarrow_offset_vert', 'text', array(
            'name'      => 'leftarrow_offset_vert',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Left Arrow Vertical Offset'),
            'title'     => Mage::helper('revslider')->__('Left Arrow Vertical Offset'),
            'value'     => $model->getData('leftarrow_offset_vert') ? $model->getData('leftarrow_offset_vert') : 0,
            'note'      => Mage::helper('revslider')->__('Offset from current Vertical  position of Bullets / Thumbnails negative and positive direction')
        ));
        $arrow15 = $fieldset2->addField('rightarrow_align_hor', 'select', array(
            'name'      => 'rightarrow_align_hor',
            'label'     => Mage::helper('revslider')->__('Right Arrow Horizontal Align'),
            'title'     => Mage::helper('revslider')->__('Right Arrow Horizontal Align'),
            'values'    => $model->getLCROptions(),
            'value'     => $model->getData('rightarrow_align_hor') ? $model->getData('rightarrow_align_hor') : 'right',
            'note'      => Mage::helper('revslider')->__('Vertical Align of right Arrow (only if arrow is not next to bullets)')
        ));
        $arrow16 = $fieldset2->addField('rightarrow_align_vert', 'select', array(
            'name'      => 'rightarrow_align_vert',
            'label'     => Mage::helper('revslider')->__('Right Arrow Vertical Align'),
            'title'     => Mage::helper('revslider')->__('Right Arrow Vertical Align'),
            'values'    => $model->getTCBOptions(),
            'value'     => $model->getData('rightarrow_align_vert') ? $model->getData('rightarrow_align_vert') : 'center',
            'note'      => Mage::helper('revslider')->__('Vertical Align of right Arrow (only if arrow is not next to bullets)')
        ));
        $arrow17 = $fieldset2->addField('rightarrow_offset_hor', 'text', array(
            'name'      => 'rightarrow_offset_hor',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Right Arrow Horizontal Offset'),
            'title'     => Mage::helper('revslider')->__('Right Arrow Horizontal Offset'),
            'value'     => $model->getData('rightarrow_offset_hor') ? $model->getData('rightarrow_offset_hor') : 20,
            'note'      => Mage::helper('revslider')->__('Offset from current Horizontal position of of right Arrow negative and positive direction')
        ));
        $arrow18 = $fieldset2->addField('rightarrow_offset_vert', 'text', array(
            'name'      => 'rightarrow_offset_vert',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Right Arrow Vertical Offset'),
            'title'     => Mage::helper('revslider')->__('Right Arrow Vertical Offset'),
            'value'     => $model->getData('rightarrow_offset_vert') ? $model->getData('rightarrow_offset_vert') : 0,
            'note'      => Mage::helper('revslider')->__('Offset from current Vertical position of of right Arrow negative and positive direction')
        ));

        $this->setForm($form);
        if ($model->getId()){
            $form->setValues($model->getData());
        }

        $arrowDependValues = array('round', 'navbar', 'preview2', 'custom', 'round-old', 'square-old', 'navbar-old');
        $bulletDependValues = array('bullet', 'thumb');

        if (version_compare(Mage::getVersion(), '1.7.0.0') < 0){
            $dependenceElement = $this->getLayout()->createBlock('mtext/adminhtml_widget_form_element_dependence');
        }else{
            $dependenceElement = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        }

        $this->setChild('form_after', $dependenceElement
            ->addFieldMap($nav->getHtmlId(), $nav->getName())
            ->addFieldMap($nav11->getHtmlId(), $nav11->getName())
            ->addFieldMap($nav12->getHtmlId(), $nav12->getName())
            ->addFieldMap($nav13->getHtmlId(), $nav13->getName())
            ->addFieldMap($nav14->getHtmlId(), $nav14->getName())
            ->addFieldMap($arrow->getHtmlId(), $arrow->getName())
            ->addFieldMap($arrow11->getHtmlId(), $arrow11->getName())
            ->addFieldMap($arrow12->getHtmlId(), $arrow12->getName())
            ->addFieldMap($arrow13->getHtmlId(), $arrow13->getName())
            ->addFieldMap($arrow14->getHtmlId(), $arrow14->getName())
            ->addFieldMap($arrow15->getHtmlId(), $arrow15->getName())
            ->addFieldMap($arrow16->getHtmlId(), $arrow16->getName())
            ->addFieldMap($arrow17->getHtmlId(), $arrow17->getName())
            ->addFieldMap($arrow18->getHtmlId(), $arrow18->getName())
            ->addFieldMap($show->getHtmlId(), $show->getName())
            ->addFieldMap($show1->getHtmlId(), $show1->getName())
            ->addFieldDependence($nav11->getName(), $nav->getName(), $bulletDependValues)
            ->addFieldDependence($nav12->getName(), $nav->getName(), $bulletDependValues)
            ->addFieldDependence($nav13->getName(), $nav->getName(), $bulletDependValues)
            ->addFieldDependence($nav14->getName(), $nav->getName(), $bulletDependValues)
            ->addFieldDependence($arrow11->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($arrow12->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($arrow13->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($arrow14->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($arrow15->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($arrow16->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($arrow17->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($arrow18->getName(), $arrow->getName(), $arrowDependValues)
            ->addFieldDependence($show1->getName(), $show->getName(), 'false')
        );

        return parent::_prepareForm();
    }
}
