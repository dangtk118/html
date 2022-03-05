/**
 * @category    AM
 * @package     AM_Extensions
 * @copyright   Copyright (C) 2008-2013 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

delete window['console'];

if (typeof disableElement === 'undefined'){
    var disableElement = function(elem){
        elem.disabled = true;
        elem.addClassName('disabled');
    }
}

if (typeof enableElement === 'undefined'){
    var enableElement = function(elem){
        elem.disabled = false;
        elem.removeClassName('disabled');
    }
}
