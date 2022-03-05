<?php
require_once 'abstract.php';
class Mage_Shell_SearchIndex extends Mage_Shell_Abstract
{
    public function run()
    {
        if ( !class_exists("Sonassi_FastSearchIndex_AdminController"))
        {
            require_once('Sonassi/FastSearchIndex/controllers/AdminController.php');
        }

        $controller = new Sonassi_FastSearchIndex_AdminController(
                Mage::app()->getRequest(),
                Mage::app()->getResponse());

        $controller->refreshSearchAction();
    }
}

require_once str_replace('shell','',getcwd()) . 'app/Mage.php';
$shell = new Mage_Shell_SearchIndex();
$shell->run();
