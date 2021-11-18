<?php

/**
 * Class DPD_Connect_Model_Adminhtml_System_Config_Backend_Connect_Dpdclassic_Tablerate
 */
class DPD_Connect_Model_Adminhtml_System_Config_Backend_Connect_Dpdclassic_Tablerate extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the classic tablerate recourcemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('dpd/dpdclassic_tablerate')->uploadAndImport($this);
    }
}