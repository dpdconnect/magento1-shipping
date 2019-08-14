<?php

/**
 * Class DPD_Connect_Model_Mysql4_Returnlabels
 */
class DPD_Connect_Model_Mysql4_Returnlabels extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Sets model primary key.
     */
    protected function _construct()
    {
        $this->_init("dpd/returnlabels", "returnlabels_id");
    }
}