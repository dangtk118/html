<?php

class Fahasa_Tabslider_Block_Tabslider extends Magentothem_Categorytabsliders_Block_Categorytabsliders_Advanced {

    public function get_prod_count($isMobile) {
        if (isset($_REQUEST['appMobile']) && $_REQUEST['appMobile'] == 1) {
            // check pageSize call from appMobile
            // default pageSize = 8 : show 8 product in APP mobile
            if (isset($_GET['limit'])) {
                $prodcount = intval($_GET['limit']);
                return $prodcount;
            }
            return 8;
        } else if ($isMobile) {
            if ($this->getNDisplayOnMobile() > 0) {
                return $this->getNDisplayOnMobile();
            }
            if ($this->getNumberOfDisplayItem()) {
                return $this->getNumberOfDisplayItem();
            }
            return 8;
        } else if ($this->getNumberOfDisplayItem()) {
            return $this->getNumberOfDisplayItem();
        } else{
            return 24;
        }
    }
}
