<?php
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$installer->addAttribute('catalog_product', 'dpd_shipping_type', array(
    'group'           => 'DPD Product Attributes',
    'label'           => 'DPD Shipping Product',
    'input'           => 'select',
    'type'            => 'varchar',
    'required'        => 0,
    'visible_on_front'=> 1,
    'filterable'      => 0,
    'searchable'      => 0,
    'comparable'      => 0,
    'user_defined'    => 1,
    'is_configurable' => 0,
    'global'          => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note'            => '',
    'source'          => 'dpd/system_config_source_shippingtype'
));

$installer->addAttribute('catalog_product', 'dpd_fresh_description', array(
    'group'           => 'DPD Product Attributes',
    'label'           => 'DPD Fresh Shipping Description',
    'input'           => 'text',
    'backend'         => 'dpd/adminhtml_system_config_backend_shipping_shippingdescription',
    'type'            => 'varchar',
    'required'        => 0,
    'visible_on_front'=> 1,
    'filterable'      => 0,
    'searchable'      => 0,
    'comparable'      => 0,
    'user_defined'    => 1,
    'is_configurable' => 0,
    'global'          => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note'            => '',
    'source'          => ''
));

$installer->endSetup();
