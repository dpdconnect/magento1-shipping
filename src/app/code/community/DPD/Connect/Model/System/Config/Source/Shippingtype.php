<?php

class DPD_Connect_Model_System_Config_Source_Shippingtype extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Get all options
     * @return array
     */
    public function getAllOptions()
    {
        $availableProducts =  Mage::getSingleton('dpd/webservice')->getAvailableProducts();

        $availableCodes = array_map(function($product) {
            return $product['code'];
        }, $availableProducts);

        $options = [
            ['label' => Mage::helper('dpd')->__('Default'), 'value' => 'default'],
        ];

        if (true === in_array('FRESH', $availableCodes)) {
            $options[] = ['label' => Mage::helper('dpd')->__('Fresh'), 'value' => 'fresh'];
        }

        if (true === in_array('FREEZE', $availableCodes)) {
            $options[] = ['label' => Mage::helper('dpd')->__('Freeze'), 'value' => 'freeze'];
        }

        return $options;
    }
}