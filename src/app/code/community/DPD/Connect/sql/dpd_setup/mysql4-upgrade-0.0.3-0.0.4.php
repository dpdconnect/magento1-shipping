<?php

$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'dpd_label_exported', "int(11) null");
$installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'dpd_label_path', "varchar(255) null default ''");
$installer->getConnection()->dropColumn($installer->getTable('sales/order'), 'dpd_label_exported');
$installer->endSetup();