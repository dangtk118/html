/**
 * @category    AM
 * @package     AM_Extensions
 * @copyright   Copyright (C) 2008-2013 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */
'use strict';

document.observe('dom:loaded', function(){
    function toggleFielsetCollapse(fieldset, legend, flag){
        if (flag){
            legend.down('a').addClassName('open');
            legend.removeClassName('collapsed').setStyle({marginBottom: '0px'});
            fieldset.show();
        }else{
            legend.down('a').removeClassName('open');
            legend.addClassName('collapsed').setStyle({marginBottom: '3px'});
            fieldset.hide();
        }
    }

    var checkFieldsetInGroupPattern = new RegExp('collapse-group', 'i'),
        collapseIndex = 0,
        fielsetCollection = $$('div.collapsible.fieldset');

    Event.observe(document, 'collapse:open', function(ev){
        fielsetCollection.each(function(fieldset){
            if (fieldset.collapseIndex != ev.memo.index) {
                if (checkFieldsetInGroupPattern.test(fieldset.className)) {
                    var legend = fieldset.previous();
                    if (legend.hasClassName('entry-edit-head')){
                        if (fieldset.hasClassName('collapsed')){
                            toggleFielsetCollapse(fieldset, legend, true);
                        }else{
                            toggleFielsetCollapse(fieldset, legend, false);
                        }
                    }
                }
            }
        });
    });

    fielsetCollection.each(function(fieldset){
        fieldset.setStyle({marginTop: '0px'});

        var legend = fieldset.previous();
        if (legend.hasClassName('entry-edit-head')){
            var isFieldsetInGroup = checkFieldsetInGroupPattern.test(fieldset.className);

            legend.addClassName('collapseable');
            legend.setStyle({cursor: 'pointer'});

            var a = new Element('a', {'class': 'open'});
            a.setStyle({width: '20px', height: '16px'});
            legend.down('.form-buttons').appendChild(a);

            legend.observe('click', function(){
                if (this.hasClassName('collapsed')){
                    toggleFielsetCollapse(fieldset, legend, true);
                    isFieldsetInGroup && Element.fire(fieldset, 'collapse:open', {index: fieldset.collapseIndex});
                }else{
                    toggleFielsetCollapse(fieldset, legend, false);
                }
            });

            if (isFieldsetInGroup){
                fieldset.collapseIndex = collapseIndex++;

                if (fieldset.hasClassName('collapse-active')){
                    toggleFielsetCollapse(fieldset, legend, true);
                }else{
                    toggleFielsetCollapse(fieldset, legend, false);
                }
            }
        }
    });
});

var AM = AM || {};
AM.FormElementDependenceController = Class.create(FormElementDependenceController, {
    /**
     * Define whether target element should be toggled and show/hide its row
     *
     * @param e - object event
     * @param idTo - id of target element
     * @param valuesFrom - ids of master elements and reference values
     */
    trackChange : function(e, idTo, valuesFrom) {
        // define whether the target should show up
        var shouldShowUp = true;
        for (var idFrom in valuesFrom) {
            var from = $(idFrom);
            if (valuesFrom[idFrom] instanceof Array) {
                if (!from || valuesFrom[idFrom].indexOf(from.value) == -1) {
                    shouldShowUp = false;
                }
            } else {
                if (!from){
                    shouldShowUp = false;
                }else if (from.type == 'checkbox'){
                    if (from.checked != valuesFrom[idFrom]){
                        shouldShowUp = false;
                    }
                }else if (from.value != valuesFrom[idFrom]){
                    shouldShowUp = false;
                }
            }
        }

        // toggle target row
        if (shouldShowUp) {
            var currentConfig = this._config;
            $(idTo).up(this._config.levels_up).select('input', 'select', 'td').each(function (item) {
                // don't touch hidden inputs (and Use Default inputs too), bc they may have custom logic
                if ((!item.type || item.type != 'hidden')
                    && !($(item.id+'_inherit') && $(item.id+'_inherit').checked)
                    && !(currentConfig.can_edit_price != undefined && !currentConfig.can_edit_price)) {
                    item.disabled = false;
                }
            });
            $(idTo).up(this._config.levels_up).show();
        } else {
            $(idTo).up(this._config.levels_up).select('input', 'select', 'td').each(function (item){
                // don't touch hidden inputs (and Use Default inputs too), bc they may have custom logic
                if ((!item.type || item.type != 'hidden') && !($(item.id+'_inherit') && $(item.id+'_inherit').checked)) {
                    item.disabled = true;
                }
            });
            $(idTo).up(this._config.levels_up).hide();
        }
    }
});
