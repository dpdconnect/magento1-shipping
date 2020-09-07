<?php

/**
 * Class DPD_Connect_Model_System_Config_Backend_Connect_Specialparcelshops
 */
class DPD_Connect_Model_Adminhtml_System_Config_Backend_Shipping_Specialparcelshops extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the parcelshops recoursemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('dpd/specialparcelshops')->uploadAndImport($this);
    }
}
