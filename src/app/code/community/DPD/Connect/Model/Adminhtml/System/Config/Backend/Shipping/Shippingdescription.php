<?php


class DPD_Connect_Model_Adminhtml_System_Config_Backend_Shipping_Shippingdescription extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract
{
    public function validate($object)
    {
        parent::validate($object);

        $selectedDpdShippingProduct = $object->getData('dpd_shipping_type');

        if (true === in_array($selectedDpdShippingProduct, ['fresh', 'freeze'])) {
            $value = $object->getData($this->getAttribute()->getAttributeCode());

            if ('' === $value || null === $value) {
                Mage::throwException(Mage::helper('dpd')->__('With Fresh and Freeze products a description is mandatory'));
                return false;
            }
        }

        return true;
    }
}