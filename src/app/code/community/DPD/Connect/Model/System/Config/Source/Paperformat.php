<?php

/**
 * Class DPD_Connect_Model_System_Config_Source_Paperformat
 */
class DPD_Connect_Model_System_Config_Source_Paperformat
{
    /**
     * Options getter.
     * Returns an option array for Label pdf size.
     *
     * @return array
     *
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => Mage::helper('dpd')->__('A4')),
            array('value' => 0, 'label' => Mage::helper('dpd')->__('A6')),
        );
    }

    /**
     * Get options in "key-value" format.
     * Returns an array for Label pdf size. (Magento basically expects both functions)
     *
     * @return array
     *
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('dpd')->__('A6'),
            1 => Mage::helper('dpd')->__('A4'),
        );
    }

}
