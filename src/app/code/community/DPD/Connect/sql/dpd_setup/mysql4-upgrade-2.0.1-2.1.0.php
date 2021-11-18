<?php

$installer = $this;
$installer->startSetup();
// add quote attributes

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_house_number', "varchar(255) null default ''");

$installer->endSetup();