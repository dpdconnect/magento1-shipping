<?php

/**
 * Class DPD_Connect_Model_Adminhtml_System_Config_Backend_Connect_Dpdparcelshops_Tablerate
 */
class DPD_Connect_Model_Adminhtml_System_Config_Backend_Shipping_Dpdparcelshops_Tablerate extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the parcelshops tablerate recourcemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('dpd/dpdparcelshops_tablerate')->uploadAndImport($this);
    }
}
