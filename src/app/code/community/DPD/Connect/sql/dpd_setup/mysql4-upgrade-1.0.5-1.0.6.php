<?php

$installer = $this;
$installer->startSetup();
// add quote attributes

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_special_point', "boolean default '0'");

$installer->endSetup();