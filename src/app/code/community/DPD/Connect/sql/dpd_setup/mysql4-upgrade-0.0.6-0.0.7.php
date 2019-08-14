<?php

$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'dpd_tracking_url', "varchar(255) null default ''");
$installer->endSetup();