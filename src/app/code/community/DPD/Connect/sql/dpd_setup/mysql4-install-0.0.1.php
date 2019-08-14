<?php

$installer = $this;
$installer->startSetup();
// add quote attributes

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_selected', "boolean default '0'");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_company', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_city', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_street', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_zipcode', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_country', "varchar(255) null default ''");

$installer->endSetup();