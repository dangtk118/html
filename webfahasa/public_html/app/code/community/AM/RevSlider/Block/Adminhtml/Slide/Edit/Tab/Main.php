<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slide_Edit_Tab_Main
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function _prepareForm(){
        /* @var $slider AM_RevSlider_Model_Slider */
        $slider     = Mage::registry('revslider');
        /* @var $slide AM_RevSlider_Model_Slide */
        $slide      = Mage::registry('revslide');

        $form       = new Varien_Data_Form();

        $fieldset   = $form->addFieldset(
            'general_fieldset', array(
                'legend'    => Mage::helper('revslider')->__('General Slide Settings'),
                'class'     => 'collapsible'
            )
        );
        if ($slider->getId()){
            $fieldset->addField('slider_id', 'hidden', array(
                'name'  => 'slider_id',
                'value' => $slider->getId()
            ));
        }
        if ($slide->getId()){
            $fieldset->addField('id', 'hidden', array(
                'name'  => 'id',
                'value' => $slide->getId()
            ));
        }
        $fieldset->addField('title', 'text', array(
            'name'      => 'title',
            'required'  => true,
            'label'     => Mage::helper('revslider')->__('Slide Title'),
            'title'     => Mage::helper('revslider')->__('Slide Title'),
            'note'      => Mage::helper('revslider')->__('The title of the slide, will be shown in the slides list')
        ));
        $fieldset->addField('state', 'select', array(
            'name'      => 'state',
            'label'     => Mage::helper('revslider')->__('State'),
            'title'     => Mage::helper('revslider')->__('State'),
            'note'      => Mage::helper('revslider')->__('The state of the slide. The unpublished slide will be excluded from the slider'),
            'values'    => $slide->getPublishOptions()
        ));
        $fieldset->addField('date_from', 'date', array(
            'name'      => 'date_from',
            'label'     => Mage::helper('revslider')->__('Visible From'),
            'title'     => Mage::helper('revslider')->__('Visible From'),
            'note'      => Mage::helper('revslider')->__('If set, slide will be visible after the date is reached'),
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'format'    => Mage::app()->getLocale()->getDateFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
            )
        ));
        $fieldset->addField('date_to', 'date', array(
            'name'      => 'date_to',
            'label'     => Mage::helper('revslider')->__('Visible Until'),
            'title'     => Mage::helper('revslider')->__('Visible Until'),
            'note'      => Mage::helper('revslider')->__('If set, slide will be visible till the date is reached'),
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'format'    => Mage::app()->getLocale()->getDateFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
            )
        ));
        $fieldset->addField('slide_transition', 'multiselect', array(
            'name'      => 'transitions',
            'label'     => Mage::helper('revslider')->__('Transitions'),
            'title'     => Mage::helper('revslider')->__('Transitions'),
            'note'      => Mage::helper('revslider')->__('The appearance transitions of this slide'),
            'values'    => $slider->getTransistionOptions(),
            'value'     => $slide->getData('slide_transition') ? $slide->getData('slide_transition') : array('random')
        ));
        $fieldset->addField('slot_amount', 'text', array(
            'name'      => 'slot_amount',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Slot Amount'),
            'title'     => Mage::helper('revslider')->__('Slot Amount'),
            'note'      => Mage::helper('revslider')->__('The number of slots or boxes the slide is divided into. If you use boxfade, over 7 slots can be juggy'),
            'value'     => $slide->getData('slot_amount') ? $slide->getData('slot_amount') : 7
        ));
        $fieldset->addField('transition_rotation', 'text', array(
            'name'      => 'transition_rotation',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Rotation'),
            'title'     => Mage::helper('revslider')->__('Rotation'),
            'note'      => Mage::helper('revslider')->__('Rotation (-720 → 720, 999 = random) Only for Simple Transitions'),
            'value'     => $slide->getData('transition_rotation') ? $slide->getData('transition_rotation') : 0
        ));
        $fieldset->addField('transition_duration', 'text', array(
            'name'      => 'transition_duration',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Transition Duration'),
            'title'     => Mage::helper('revslider')->__('Transition Duration'),
            'note'      => Mage::helper('revslider')->__('The duration of the transition (Default:300, min: 100 max 2000)'),
            'value'     => $slide->getData('transition_duration') ? $slide->getData('transition_duration') : 500
        ));
        $fieldset->addField('delay', 'text', array(
            'name'      => 'delay',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Delay'),
            'title'     => Mage::helper('revslider')->__('Delay'),
            'note'      => Mage::helper('revslider')->__('A new delay value for the Slide. If no delay defined per slide, the delay defined via Options (5000ms) will be used')
        ));
        $fieldset->addField('save_performance', 'select', array(
            'name'      => 'save_performance',
            'label'     => Mage::helper('revslider')->__('Save Performance'),
            'title'     => Mage::helper('revslider')->__('Save Performance'),
            'values'    => $slider->getOnOffOptions()
        ));
        $link = $fieldset->addField('enable_link', 'select', array(
            'name'      => 'enable_link',
            'label'     => Mage::helper('revslider')->__('Enable Link'),
            'title'     => Mage::helper('revslider')->__('Enable Link'),
            'values'    => $slider->getYesNoOptions()
        ));
        $link1 = $fieldset->addField('link_type', 'select', array(
            'name'      => 'link_type',
            'label'     => Mage::helper('revslider')->__('Link Type'),
            'title'     => Mage::helper('revslider')->__('Link Type'),
            'values'    => $slide->getLinkTypeOptions()
        ));
        $link2 = $fieldset->addField('link', 'text', array(
            'name'      => 'link',
            'label'     => Mage::helper('revslider')->__('Slide Link'),
            'title'     => Mage::helper('revslider')->__('Slide Link'),
            'note'      => Mage::helper('revslider')->__('A link on the whole slide pic')
        ));
        $link3 = $fieldset->addField('link_open_in', 'select', array(
            'name'      => 'link_open_in',
            'label'     => Mage::helper('revslider')->__('Link Open In'),
            'title'     => Mage::helper('revslider')->__('Link Open In'),
            'note'      => Mage::helper('revslider')->__('The target of the slide link'),
            'values'    => $slide->getLinkTargetOptions()
        ));
        $link4 = $fieldset->addField('slide_link', 'select', array(
            'name'      => 'slide_link',
            'label'     => Mage::helper('revslider')->__('Link To Slide'),
            'title'     => Mage::helper('revslider')->__('Link To Slide'),
            'values'    => $slider->getLinkSlideOptions(array($slide ? $slide->getId() : 0))
        ));
        $link5 = $fieldset->addField('link_pos', 'select', array(
            'name'      => 'link_pos',
            'label'     => Mage::helper('revslider')->__('Link Position'),
            'title'     => Mage::helper('revslider')->__('Link Position'),
            'note'      => Mage::helper('revslider')->__('The position of the link related to layers'),
            'values'    => $slide->getLinkPosOptions()
        ));
        $thumb = $fieldset->addField('slide_thumb', 'text', array(
            'name'      => 'slide_thumb',
            'label'     => Mage::helper('revslider')->__('Thumbnail'),
            'title'     => Mage::helper('revslider')->__('Thumbnail'),
            'note'      => Mage::helper('revslider')->__('Slide Thumbnail. If not set - it will be taken from the slide image')
        ));
        $form->getElement('slide_thumb')->setRenderer(
            $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_browser', '', array(
                'element' => $thumb
            ))
        );
        $fieldset->addField('class_attr', 'text', array(
            'name'      => 'class_attr',
            'label'     => Mage::helper('revslider')->__('Classes'),
            'title'     => Mage::helper('revslider')->__('Classes'),
            'note'      => Mage::helper('revslider')->__('Adds a unique class to the li of the Slide like class="rev_special_class" (add only the classnames, seperated by space)')
        ));
        $fieldset->addField('id_attr', 'text', array(
            'name'      => 'id_attr',
            'label'     => Mage::helper('revslider')->__('ID'),
            'title'     => Mage::helper('revslider')->__('ID'),
            'note'      => Mage::helper('revslider')->__('Adds a unique ID to the li of the Slide like id="rev_special_id" (add only the id)')
        ));
        /*$fieldset->addField('attr_attr', 'text', array(
            'name'      => 'attr_attr',
            'label'     => Mage::helper('revslider')->__('Attribute'),
            'title'     => Mage::helper('revslider')->__('Attribute'),
            'note'      => Mage::helper('revslider')->__('Adds a unique Attribute to the li of the Slide like attr="rev_special_attr" (add only the attribute)')
        ));*/
        $fieldset->addField('data_attr', 'text', array(
            'name'      => 'data_attr',
            'label'     => Mage::helper('revslider')->__('Custom Fields'),
            'title'     => Mage::helper('revslider')->__('Custom Fields'),
            'note'      => Mage::helper('revslider')->__('Add as many attributes as you wish here. (i.e.: data-layer="firstlayer" data-custom="somevalue")')
        ));

        $fieldset2 = $form->addFieldset('layers_fieldset', array(
            'legend'    => Mage::helper('revslider')->__('Slide Image and Layers')
        ));
        $bg = $fieldset2->addField('background_type', 'select', array(
            'name'      => 'background_type',
            'label'     => Mage::helper('revslider')->__('Background Type'),
            'title'     => Mage::helper('revslider')->__('Background Type'),
            'values'    => $slide->getBackgroundTypeOptions(),
            'value'     => $slide->getBgType() ? $slide->getBgType() : 'trans',
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg1 = $fieldset2->addField('image_url', 'text', array(
            'name'      => 'image_url',
            'label'     => Mage::helper('revslider')->__('Background Image'),
            'title'     => Mage::helper('revslider')->__('Background Image'),
            'onchange'  => 'revLayer.updateContainer()',
            'readonly'  => true
        ));
        $form->getElement('image_url')->setRenderer(
            $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_browser', '', array(
                'element' => $bg1
            ))
        );
        $bg2 = $fieldset2->addField('slide_bg_color', 'text', array(
            'name'      => 'slide_bg_color',
            'label'     => Mage::helper('revslider')->__('Background Color'),
            'title'     => Mage::helper('revslider')->__('Background Color'),
            'class'     => 'color',
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg3 = $fieldset2->addField('bg_external', 'text', array(
            'name'      => 'bg_external',
            'label'     => Mage::helper('revslider')->__('External URL'),
            'title'     => Mage::helper('revslider')->__('External URL')
        ));
        $form->getElement('bg_external')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_url')
        );
        $bg4 = $fieldset2->addField('bg_fit', 'select', array(
            'name'      => 'bg_fit',
            'label'     => Mage::helper('revslider')->__('Background Fit'),
            'title'     => Mage::helper('revslider')->__('Background Fit'),
            'values'    => $slide->getBackgroundSizeOptions(),
            'value'     => $slide->getBgFit() ? $slide->getBgFit() : 'cover',
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg41 = $fieldset2->addField('bg_fit_x', 'text', array(
            'name'      => 'bg_fit_x',
            'label'     => Mage::helper('revslider')->__('Background Fit X'),
            'title'     => Mage::helper('revslider')->__('Background Fit X'),
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg42 = $fieldset2->addField('bg_fit_y', 'text', array(
            'name'      => 'bg_fit_y',
            'label'     => Mage::helper('revslider')->__('Background Fit Y'),
            'title'     => Mage::helper('revslider')->__('Background Fit Y'),
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg7 = $fieldset2->addField('bg_repeat', 'select', array(
            'name'      => 'bg_repeat',
            'label'     => Mage::helper('revslider')->__('Background Repeat'),
            'title'     => Mage::helper('revslider')->__('Background Repeat'),
            'values'    => $slide->getBackgroundRepeatOptions(),
            'value'     => $slide->getBgRepeat() ? $slide->getBgRepeat() : 'no-repeat',
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg5 = $fieldset2->addField('bg_position', 'select', array(
            'name'      => 'bg_position',
            'label'     => Mage::helper('revslider')->__('Background Position'),
            'title'     => Mage::helper('revslider')->__('Background Position'),
            'values'    => $slide->getBackgroundPositionOptions(),
            'value'     => $slide->getBgPosition() ? $slide->getBgPosition() : 'left top',
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg51 = $fieldset2->addField('bg_position_x', 'text', array(
            'name'      => 'bg_position_x',
            'label'     => Mage::helper('revslider')->__('Background Position X'),
            'title'     => Mage::helper('revslider')->__('Background Position X'),
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg52 = $fieldset2->addField('bg_position_y', 'text', array(
            'name'      => 'bg_position_y',
            'label'     => Mage::helper('revslider')->__('Background Position Y'),
            'title'     => Mage::helper('revslider')->__('Background Position Y'),
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg6 = $fieldset2->addField('kenburn_effect', 'select', array(
            'name'      => 'kenburn_effect',
            'label'     => Mage::helper('revslider')->__('Ken Burns / Pan Zoom Settings'),
            'values'    => $slider->getOnOffOptions(),
            'onchange'  => 'revLayer.updateContainer()'
        ));
        $bg62 = $fieldset2->addField('kb_start_fit', 'text', array(
            'name'      => 'kb_start_fit',
            'label'     => Mage::helper('revslider')->__('Start Fit: (in %)'),
            'value'     => $slide->getKbStartFit() ? $slide->getKbStartFit() : '100',
        ));
        $bg63 = $fieldset2->addField('bg_end_position', 'select', array(
            'name'      => 'bg_end_position',
            'label'     => Mage::helper('revslider')->__('Background End Position'),
            'values'    => $slide->getBackgroundPositionOptions(),
            'value'     => $slide->getBgEndPosition() ? $slide->getBgEndPosition() : 'center top',
        ));
        $bg631 = $fieldset2->addField('bg_end_position_x', 'text', array(
            'name'      => 'bg_end_position_x',
            'label'     => Mage::helper('revslider')->__('Background End Position X')
        ));
        $bg632 = $fieldset2->addField('bg_end_position_y', 'text', array(
            'name'      => 'bg_end_position_y',
            'label'     => Mage::helper('revslider')->__('Background End Position Y')
        ));
        $bg64 = $fieldset2->addField('kb_end_fit', 'text', array(
            'name'      => 'kb_end_fit',
            'label'     => Mage::helper('revslider')->__('End Fit: (in %)'),
            'value'     => $slide->getKbEndFit() ? $slide->getKbEndFit() : '100',
        ));
        $bg65 = $fieldset2->addField('kb_easing', 'select', array(
            'name'      => 'kb_easing',
            'label'     => Mage::helper('revslider')->__('Easing'),
            'values'    => $slide->getLayerKenburnEaseOptions()
        ));
        $bg66 = $fieldset2->addField('kb_duration', 'text', array(
            'name'      => 'kb_duration',
            'label'     => Mage::helper('revslider')->__('Duration (in ms)'),
            'value'     => $slide->getKbDuration() ? $slide->getKbDuration() : $slider->getDelay()
        ));

        $fieldset2->addField('layers', 'text', array(
            'label'     => Mage::helper('revslider')->__('Layers')
        ));
        $form->getElement('layers')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_layers')
        );

        $container = $form->addFieldset('container_fieldset', array(
            'class'     => 'no-spacing'
        ));

        $left = $container->addFieldset('left_fieldset', array(
            'class'     => 'no-spacing'
        ));

        $fieldset3 = $left->addFieldset('layer_params_fieldset', array(
            'legend'    => Mage::helper('revslider')->__('Layer General Parameters'),
            'class'     => 'collapsible collapse-group collapse-active'
        ));
        $fieldset3->addField('layer_style', 'select', array(
            'label'     => Mage::helper('revslider')->__('Style'),
            'title'     => Mage::helper('revslider')->__('Style'),
            'values'    => Mage::getModel('revslider/css')->getCollection()->toOptionArray()
        ));
        $fieldset3->addField('layer_css', 'text', array());
        $form->getElement('layer_css')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_css')
        );
        $fieldset3->addField('layer_text', 'textarea', array(
            'label'     => Mage::helper('revslider')->__('Text / Html'),
            'title'     => Mage::helper('revslider')->__('Text / Html')
        ));
        $fieldset3->addField('layer_align', 'text', array(
            'label'     => Mage::helper('revslider')->__('Align')
        ));
        $form->getElement('layer_align')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_align')
        );
        $fieldset3->addField('layer_left', 'text', array(
            'label'     => Mage::helper('revslider')->__('Offset X'),
            'title'     => Mage::helper('revslider')->__('Offset X'),
            'class'     => 'validate-number'
        ));
        $fieldset3->addField('layer_top', 'text', array(
            'label'     => Mage::helper('revslider')->__('Offset Y'),
            'title'     => Mage::helper('revslider')->__('Offset Y'),
            'class'     => 'validate-number'
        ));
        $fieldset3->addField('layer_whitespace', 'select', array(
            'label'     => Mage::helper('revslider')->__('White Space'),
            'title'     => Mage::helper('revslider')->__('White Space'),
            'values'    => $slide->getLayerWhiteSpaceOptions()
        ));
        $fieldset3->addField('layer_max_width', 'text', array(
            'label'     => Mage::helper('revslider')->__('Max Width'),
            'title'     => Mage::helper('revslider')->__('Max Width'),
            'note'      => Mage::helper('revslider')->__('Example: 50px, 50%, auto')
        ));
        $fieldset3->addField('layer_max_height', 'text', array(
            'label'     => Mage::helper('revslider')->__('Max Height'),
            'title'     => Mage::helper('revslider')->__('Max Height'),
            'note'      => Mage::helper('revslider')->__('Example: 50px, 50%, auto')
        ));
        $fieldset3->addField('layer_proportional_scale', 'checkbox', array(
            'label'     => Mage::helper('revslider')->__('Scale Proportional'),
            'title'     => Mage::helper('revslider')->__('Scale Proportional')
        ));
        $fieldset3->addField('layer_scaleX', 'text', array(
            'label'     => Mage::helper('revslider')->__('Width'),
            'title'     => Mage::helper('revslider')->__('Width'),
            'class'     => 'validate-number'
        ));
        $fieldset3->addField('layer_scaleY', 'text', array(
            'label'     => Mage::helper('revslider')->__('Height'),
            'title'     => Mage::helper('revslider')->__('Height'),
            'class'     => 'validate-number'
        ));
        $fieldset3->addField('layer_reset_scale', 'text', array());
        $form->getElement('layer_reset_scale')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_reset')
        );
        $fieldset3->addField('layer_2d_rotation', 'text', array(
            'label'     => Mage::helper('revslider')->__('2D Rotation'),
            'title'     => Mage::helper('revslider')->__('2D Rotation'),
            'note'      => Mage::helper('revslider')->__('Degree (-360 → 360)'),
            'class'     => 'validate-number'
        ));
        $fieldset3->addField('layer_2d_origin_x', 'text', array(
            'label'     => Mage::helper('revslider')->__('Rotation Origin X'),
            'title'     => Mage::helper('revslider')->__('Rotation Origin X'),
            'note'      => Mage::helper('revslider')->__('% (-100 → 200)'),
            'class'     => 'validate-number'
        ));
        $fieldset3->addField('layer_2d_origin_y', 'text', array(
            'label'     => Mage::helper('revslider')->__('Rotation Origin Y'),
            'title'     => Mage::helper('revslider')->__('Rotation Origin Y'),
            'note'      => Mage::helper('revslider')->__('% (-100 → 200)'),
            'class'     => 'validate-number'
        ));
        $fieldset3->addField('layer_parallax_level', 'select', array(
            'label'     => Mage::helper('revslider')->__('Parallax Level'),
            'title'     => Mage::helper('revslider')->__('Parallax Level'),
            'values'    => $slide->getLayerParallaxLevelOptions()
        ));

        $fieldset4 = $left->addFieldset('layer_animate_fieldset', array(
            'legend'    => Mage::helper('revslider')->__('Layer Animation '),
            'class'     => 'collapsible collapse-group-1'
        ));
        $fieldset4->addField('layer_animation_preview', 'text', array(
            'label'     => Mage::helper('revslider')->__('Preview Transition'),
            'note'      => Mage::helper('revslider')->__('Preview Transition (Start Time & End Time is ignored during demo)')
        ));
        $form->getElement('layer_animation_preview')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_animation')
        );
        $fieldset4->addField('layer_animation', 'select', array(
            'label'     => Mage::helper('revslider')->__('Start Animation'),
            'title'     => Mage::helper('revslider')->__('Start Animation'),
            'values'    => $slide->getLayerAnimationOptions()
        ));
        $fieldset4->addField('custom_animation', 'text', array());
        $form->getElement('custom_animation')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_cinanimation')
        );
        $fieldset4->addField('layer_easing', 'select', array(
            'label'     => Mage::helper('revslider')->__('Start Easing'),
            'title'     => Mage::helper('revslider')->__('Start Easing'),
            'values'    => $slide->getLayerEaseOptions()
        ));
        $fieldset4->addField('layer_speed', 'text', array(
            'label'     => Mage::helper('revslider')->__('Start Duration'),
            'title'     => Mage::helper('revslider')->__('Start Duration'),
            'class'     => 'validate-number'
        ));
        $fieldset4->addField('layer_split', 'select', array(
            'label'     => Mage::helper('revslider')->__('Split Text Per'),
            'title'     => Mage::helper('revslider')->__('Split Text Per'),
            'values'    => $slide->getLayerAnimationSplitOptions()
        ));
        $fieldset4->addField('layer_splitdelay', 'text', array(
            'label'     => Mage::helper('revslider')->__('Split Delay'),
            'title'     => Mage::helper('revslider')->__('Split Delay'),
            'class'     => 'validate-number',
            'note'      => Mage::helper('revslider')->__('(ms) Keep it low 1- 200')
        ));
        $fieldset4->addField('layer_endanimation', 'select', array(
            'label'     => Mage::helper('revslider')->__('End Animation'),
            'title'     => Mage::helper('revslider')->__('End Animation'),
            'values'    => array_merge(
                array('auto' => Mage::helper('revslider')->__('Choose Automatic')),
                $slide->getLayerEndAnimationOptions()
            )
        ));
        $fieldset4->addField('custom_endanimation', 'text', array());
        $form->getElement('custom_endanimation')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_coutanimation')
        );
        $fieldset4->addField('layer_endeasing', 'select', array(
            'label'     => Mage::helper('revslider')->__('End Easing'),
            'title'     => Mage::helper('revslider')->__('End Easing'),
            'values'    => array_merge(
                array('nothing' => Mage::helper('revslider')->__('No Change')),
                $slide->getLayerEaseOptions()
            )
        ));
        $fieldset4->addField('layer_endspeed', 'text', array(
            'label'     => Mage::helper('revslider')->__('End Duration'),
            'title'     => Mage::helper('revslider')->__('End Duration'),
            'class'     => 'validate-number'
        ));
        $fieldset4->addField('layer_endsplit', 'select', array(
            'label'     => Mage::helper('revslider')->__('Split Text Per'),
            'title'     => Mage::helper('revslider')->__('Split Text Per'),
            'values'    => $slide->getLayerAnimationSplitOptions()
        ));
        $fieldset4->addField('layer_endsplitdelay', 'text', array(
            'label'     => Mage::helper('revslider')->__('Split Delay'),
            'title'     => Mage::helper('revslider')->__('Split Delay'),
            'class'     => 'validate-number',
            'note'      => Mage::helper('revslider')->__('(ms) Keep it low 1- 200')
        ));
        $loop = $fieldset4->addField('layer_loop_animation', 'select', array(
            'label'     => Mage::helper('revslider')->__('Loop Animation'),
            'title'     => Mage::helper('revslider')->__('Loop Animation'),
            'values'    => $slide->getLoopAnimationOptions()
        ));
        $loop1 = $fieldset4->addField('layer_loop_speed', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Speed'),
            'title'     => Mage::helper('revslider')->__('Loop Speed'),
            'note'      => Mage::helper('revslider')->__('0 → 10'),
            'class'     => 'validate-number'
        ));
        $loop2 = $fieldset4->addField('layer_loop_easing', 'select', array(
            'label'     => Mage::helper('revslider')->__('Loop Easing'),
            'title'     => Mage::helper('revslider')->__('Loop Easing'),
            'values'    => $slide->getLayerEaseOptions()
        ));
        $loop31 = $fieldset4->addField('layer_loop_startdeg', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Start Degree'),
            'title'     => Mage::helper('revslider')->__('Loop Start Degree'),
            'note'      => Mage::helper('revslider')->__('Degree (-720 → 720)'),
            'class'     => 'validate-number'
        ));
        $loop32 = $fieldset4->addField('layer_loop_enddeg', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop End Degree'),
            'title'     => Mage::helper('revslider')->__('Loop End Degree'),
            'note'      => Mage::helper('revslider')->__('Degree (-720 → 720)'),
            'class'     => 'validate-number'
        ));
        $loop41 = $fieldset4->addField('layer_loop_xstart', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop X Start Pos'),
            'title'     => Mage::helper('revslider')->__('Loop x Start Pos'),
            'note'      => Mage::helper('revslider')->__('px (-2000 → 2000)'),
            'class'     => 'validate-number'
        ));
        $loop42 = $fieldset4->addField('layer_loop_xend', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop X End Pos'),
            'title'     => Mage::helper('revslider')->__('Loop x End Pos'),
            'note'      => Mage::helper('revslider')->__('px (-2000 → 2000)'),
            'class'     => 'validate-number'
        ));
        $loop43 = $fieldset4->addField('layer_loop_ystart', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Y Start Pos'),
            'title'     => Mage::helper('revslider')->__('Loop Y Start Pos'),
            'note'      => Mage::helper('revslider')->__('px (-2000 → 2000)'),
            'class'     => 'validate-number'
        ));
        $loop44 = $fieldset4->addField('layer_loop_yend', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Y End Pos'),
            'title'     => Mage::helper('revslider')->__('Loop Y End Pos'),
            'note'      => Mage::helper('revslider')->__('px (-2000 → 2000)'),
            'class'     => 'validate-number'
        ));
        $loop51 = $fieldset4->addField('layer_loop_zoomstart', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Start Zoom'),
            'title'     => Mage::helper('revslider')->__('Loop Start Zoom'),
            'note'      => Mage::helper('revslider')->__('0 → 20'),
            'class'     => 'validate-number'
        ));
        $loop52 = $fieldset4->addField('layer_loop_zoomend', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop End Zoom'),
            'title'     => Mage::helper('revslider')->__('Loop End Zoom'),
            'note'      => Mage::helper('revslider')->__('0 → 20'),
            'class'     => 'validate-number'
        ));
        $loop61 = $fieldset4->addField('layer_loop_xorigin', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop X Origin'),
            'title'     => Mage::helper('revslider')->__('Loop X Origin'),
            'note'      => Mage::helper('revslider')->__('% (-250 → 250)'),
            'class'     => 'validate-number'
        ));
        $loop62 = $fieldset4->addField('layer_loop_yorigin', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Y Origin'),
            'title'     => Mage::helper('revslider')->__('Loop Y Origin'),
            'note'      => Mage::helper('revslider')->__('% (-250 → 250)'),
            'class'     => 'validate-number'
        ));
        $loop7 = $fieldset4->addField('layer_loop_angle', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Angle'),
            'title'     => Mage::helper('revslider')->__('Loop Angle'),
            'note'      => Mage::helper('revslider')->__('Degree (0 → 360)'),
            'class'     => 'validate-number'
        ));
        $loop8 = $fieldset4->addField('layer_loop_radius', 'text', array(
            'label'     => Mage::helper('revslider')->__('Loop Radius'),
            'title'     => Mage::helper('revslider')->__('Loop Radius'),
            'note'      => Mage::helper('revslider')->__('px (0 → 2000)'),
            'class'     => 'validate-number'
        ));

        $fieldset5 = $left->addFieldset('layer_advance_fieldset', array(
            'legend'    => Mage::helper('revslider')->__('Layer Links & Advanced Params'),
            'class'     => 'collapsible collapse-group-1'
        ));
        $layerLink = $fieldset5->addField('layer_link_enable', 'select', array(
            'label'     => Mage::helper('revslider')->__('Enable Link'),
            'title'     => Mage::helper('revslider')->__('Enable Link'),
            'values'    => $slider->getYesNoOptions()
        ));
        $layerLink1 = $fieldset5->addField('layer_link_type', 'select', array(
            'label'     => Mage::helper('revslider')->__('Link Type'),
            'title'     => Mage::helper('revslider')->__('Link Type'),
            'values'    => $slide->getLinkTypeOptions()
        ));
        $layerLink2 = $fieldset5->addField('layer_link', 'text', array(
            'label'     => Mage::helper('revslider')->__('Slide Link'),
            'title'     => Mage::helper('revslider')->__('Slide Link'),
            'note'      => Mage::helper('revslider')->__('A link on the layer')
        ));
        $layerLink3 = $fieldset5->addField('layer_link_open_in', 'select', array(
            'label'     => Mage::helper('revslider')->__('Link Open In'),
            'title'     => Mage::helper('revslider')->__('Link Open In'),
            'note'      => Mage::helper('revslider')->__('The target of the layer link'),
            'values'    => $slide->getLinkTargetOptions()
        ));
        $layerLink4 = $fieldset5->addField('layer_link_slide', 'select', array(
            'label'     => Mage::helper('revslider')->__('Link To Slide'),
            'title'     => Mage::helper('revslider')->__('Link To Slide'),
            'values'    => $slider->getLinkSlideOptions(array($slide ? $slide->getId() : 0))
        ));
        $fieldset5->addField('layer_corner_left', 'select', array(
            'label'     => Mage::helper('revslider')->__('Left Corner'),
            'title'     => Mage::helper('revslider')->__('Left Corner'),
            'note'      => Mage::helper('revslider')->__('Only with BG color'),
            'values'    => $slide->getCornorOptions()
        ));
        $fieldset5->addField('layer_corner_right', 'select', array(
            'label'     => Mage::helper('revslider')->__('Right Corner'),
            'title'     => Mage::helper('revslider')->__('Right Corner'),
            'note'      => Mage::helper('revslider')->__('Only with BG color'),
            'values'    => $slide->getCornorOptions()
        ));
        $fieldset5->addField('layer_resizeme', 'checkbox', array(
            'label'     => Mage::helper('revslider')->__('Responsive Through All Levels')
        ));
        $fieldset5->addField('layer_hiddenunder', 'checkbox', array(
            'label'     => Mage::helper('revslider')->__('Hide Under Width')
        ));
        $fieldset5->addField('layer_attrID', 'text', array(
            'label'     => Mage::helper('revslider')->__('Layer ID'),
            'title'     => Mage::helper('revslider')->__('Layer ID')
        ));
        $fieldset5->addField('layer_attrClasses', 'text', array(
            'label'     => Mage::helper('revslider')->__('Layer Classes'),
            'title'     => Mage::helper('revslider')->__('Layer Classes')
        ));
        $fieldset5->addField('layer_attrTitle', 'text', array(
            'label'     => Mage::helper('revslider')->__('Layer Title'),
            'title'     => Mage::helper('revslider')->__('Layer Title')
        ));
        $fieldset5->addField('layer_attrRel', 'text', array(
            'label'     => Mage::helper('revslider')->__('Layer Rel'),
            'title'     => Mage::helper('revslider')->__('Layer Rel')
        ));
        $fieldset5->addField('layer_alt', 'text', array(
            'label'     => Mage::helper('revslider')->__('Layer Alt Text'),
            'title'     => Mage::helper('revslider')->__('Layer Alt Text')
        ));

        $right = $container->addFieldset('right_fieldset', array(
            'class'     => 'no-spacing'
        ));

        $fieldset6 = $right->addFieldset('layer_time_fieldset', array(
            'legend'    => Mage::helper('revslider')->__('Layers Timing & Sorting')
        ));
        $fieldset6->addField('timing', 'text', array());
        $form->getElement('timing')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_timing')
        );

        $this->setForm($form);
        if ($slide->getId()) $form->setValues($slide->getData());

        if (version_compare(Mage::getVersion(), '1.7.0.0') < 0){
            $dependenceElement = $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_dependence');
        }else{
            $dependenceElement = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        }

        $this->setChild('form_after', $dependenceElement
            ->addFieldMap($link->getHtmlId(),   $link->getName())
            ->addFieldMap($link1->getHtmlId(),  $link1->getName())
            ->addFieldMap($link2->getHtmlId(),  $link2->getName())
            ->addFieldMap($link3->getHtmlId(),  $link3->getName())
            ->addFieldMap($link4->getHtmlId(),  $link4->getName())
            ->addFieldMap($link5->getHtmlId(),  $link5->getName())

            ->addFieldMap($bg->getHtmlId(),     $bg->getName())
            ->addFieldMap($bg1->getHtmlId(),    $bg1->getName())
            ->addFieldMap($bg2->getHtmlId(),    $bg2->getName())
            ->addFieldMap($bg3->getHtmlId(),    $bg3->getName())
            ->addFieldMap($bg4->getHtmlId(),    $bg4->getName())
            ->addFieldMap($bg41->getHtmlId(),   $bg41->getName())
            ->addFieldMap($bg42->getHtmlId(),   $bg42->getName())
            ->addFieldMap($bg5->getHtmlId(),    $bg5->getName())
            ->addFieldMap($bg51->getHtmlId(),   $bg51->getName())
            ->addFieldMap($bg52->getHtmlId(),   $bg52->getName())
            ->addFieldMap($bg6->getHtmlId(),    $bg6->getName())
            ->addFieldMap($bg62->getHtmlId(),   $bg62->getName())
            ->addFieldMap($bg63->getHtmlId(),   $bg63->getName())
            ->addFieldMap($bg631->getHtmlId(),  $bg631->getName())
            ->addFieldMap($bg632->getHtmlId(),  $bg632->getName())
            ->addFieldMap($bg64->getHtmlId(),   $bg64->getName())
            ->addFieldMap($bg65->getHtmlId(),   $bg65->getName())
            ->addFieldMap($bg66->getHtmlId(),   $bg66->getName())
            ->addFieldMap($bg7->getHtmlId(),    $bg7->getName())

            ->addFieldMap($layerLink->getHtmlId(),  'layer_link_enable')
            ->addFieldMap($layerLink1->getHtmlId(), 'layer_link_type')
            ->addFieldMap($layerLink2->getHtmlId(), 'layer_link')
            ->addFieldMap($layerLink3->getHtmlId(), 'layer_link_target')
            ->addFieldMap($layerLink4->getHtmlId(), 'layer_link_slide')

            ->addFieldMap($loop->getHtmlId(),   'layer_loop')
            ->addFieldMap($loop1->getHtmlId(),  'layer_loop_speed')
            ->addFieldMap($loop2->getHtmlId(),  'layer_loop_easing')
            ->addFieldMap($loop31->getHtmlId(), 'layer_loop_startdeg')
            ->addFieldMap($loop32->getHtmlId(), 'layer_loop_enddeg')
            ->addFieldMap($loop41->getHtmlId(), 'layer_loop_xstart')
            ->addFieldMap($loop42->getHtmlId(), 'layer_loop_xend')
            ->addFieldMap($loop43->getHtmlId(), 'layer_loop_ystart')
            ->addFieldMap($loop44->getHtmlId(), 'layer_loop_yend')
            ->addFieldMap($loop51->getHtmlId(), 'layer_loop_zoomstart')
            ->addFieldMap($loop52->getHtmlId(), 'layer_loop_zoomend')
            ->addFieldMap($loop61->getHtmlId(), 'layer_loop_xorigin')
            ->addFieldMap($loop62->getHtmlId(), 'layer_loop_yorigin')
            ->addFieldMap($loop7->getHtmlId(),  'layer_loop_angle')
            ->addFieldMap($loop8->getHtmlId(),  'layer_loop_radius')

            ->addFieldDependence($link1->getName(), $link->getName(),   'true')
            ->addFieldDependence($link2->getName(), $link1->getName(),  'regular')
            ->addFieldDependence($link2->getName(), $link->getName(),   'true')
            ->addFieldDependence($link3->getName(), $link1->getName(),  'regular')
            ->addFieldDependence($link3->getName(), $link->getName(),   'true')
            ->addFieldDependence($link4->getName(), $link1->getName(),  'slide')
            ->addFieldDependence($link4->getName(), $link->getName(),   'true')
            ->addFieldDependence($link5->getName(), $link->getName(),   'true')

            ->addFieldDependence($bg1->getName(),   $bg->getName(),     'image')
            ->addFieldDependence($bg2->getName(),   $bg->getName(),     'solid')
            ->addFieldDependence($bg3->getName(),   $bg->getName(),     'external')
            ->addFieldDependence($bg4->getName(),   $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg41->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg42->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg41->getName(),  $bg4->getName(),    'percentage')
            ->addFieldDependence($bg42->getName(),  $bg4->getName(),    'percentage')
            ->addFieldDependence($bg5->getName(),   $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg51->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg52->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg51->getName(),  $bg5->getName(),    'percentage')
            ->addFieldDependence($bg52->getName(),  $bg5->getName(),    'percentage')
            ->addFieldDependence($bg6->getName(),   $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg62->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg63->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg631->getName(), $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg632->getName(), $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg64->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg65->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg66->getName(),  $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg62->getName(),  $bg6->getName(),    'on')
            ->addFieldDependence($bg63->getName(),  $bg6->getName(),    'on')
            ->addFieldDependence($bg631->getName(), $bg6->getName(),    'on')
            ->addFieldDependence($bg631->getName(), $bg63->getName(),   'percentage')
            ->addFieldDependence($bg632->getName(), $bg63->getName(),   'percentage')
            ->addFieldDependence($bg632->getName(), $bg6->getName(),    'on')
            ->addFieldDependence($bg64->getName(),  $bg6->getName(),    'on')
            ->addFieldDependence($bg65->getName(),  $bg6->getName(),    'on')
            ->addFieldDependence($bg66->getName(),  $bg6->getName(),    'on')
            ->addFieldDependence($bg4->getName(),   $bg6->getName(),    'off')
            ->addFieldDependence($bg41->getName(),  $bg6->getName(),    'off')
            ->addFieldDependence($bg42->getName(),  $bg6->getName(),    'off')
            ->addFieldDependence($bg7->getName(),   $bg->getName(),     array('image', 'external'))
            ->addFieldDependence($bg7->getName(),   $bg6->getName(),    'off')

            ->addFieldDependence('layer_link_type',     'layer_link_enable',    'true')
            ->addFieldDependence('layer_link',          'layer_link_enable',    'true')
            ->addFieldDependence('layer_link_slide',    'layer_link_enable',    'true')
            ->addFieldDependence('layer_link_target',   'layer_link_enable',    'true')
            ->addFieldDependence('layer_link',          'layer_link_type',      'regular')
            ->addFieldDependence('layer_link_target',   'layer_link_type',      'regular')
            ->addFieldDependence('layer_link_slide',    'layer_link_type',      'slide')

            ->addFieldDependence('layer_loop_speed',    'layer_loop',   array('rs-pendulum', 'rs-slideloop', 'rs-pulse', 'rs-wave'))
            ->addFieldDependence('layer_loop_easing',   'layer_loop',   array('rs-pendulum', 'rs-slideloop', 'rs-pulse'))
            ->addFieldDependence('layer_loop_startdeg', 'layer_loop',   'rs-pendulum')
            ->addFieldDependence('layer_loop_enddeg',   'layer_loop',   'rs-pendulum')
            ->addFieldDependence('layer_loop_xorigin',  'layer_loop',   array('rs-pendulum', 'rs-wave'))
            ->addFieldDependence('layer_loop_yorigin',  'layer_loop',   array('rs-pendulum', 'rs-wave'))
            ->addFieldDependence('layer_loop_xstart',   'layer_loop',   'rs-slideloop')
            ->addFieldDependence('layer_loop_xend',     'layer_loop',   'rs-slideloop')
            ->addFieldDependence('layer_loop_ystart',   'layer_loop',   'rs-slideloop')
            ->addFieldDependence('layer_loop_yend',     'layer_loop',   'rs-slideloop')
            ->addFieldDependence('layer_loop_zoomstart','layer_loop',   'rs-pulse')
            ->addFieldDependence('layer_loop_zoomend',  'layer_loop',   'rs-pulse')
            ->addFieldDependence('layer_loop_angle',    'layer_loop',   'rs-wave')
            ->addFieldDependence('layer_loop_radius',   'layer_loop',   'rs-wave')
        );

        return parent::_prepareForm();
    }

    public function getTabLabel(){
        return Mage::helper('revslider')->__('Information');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Slider Information');
    }

    public function canShowTab(){
        return true;
    }

    public function isHidden(){
        return false;
    }
}
