<?php

class DPD_Connect_Model_System_Config_Source_Customsterms
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'DAPNP',
                'label' => Mage::helper('dpd')->__('DAP NP - D&T paid by receiver'),
            ],
            [
                'value' => 'DAPDP',
                'label' => Mage::helper('dpd')->__('DAP DP - D&T paid by sender'),
            ],
        ];
    }
}