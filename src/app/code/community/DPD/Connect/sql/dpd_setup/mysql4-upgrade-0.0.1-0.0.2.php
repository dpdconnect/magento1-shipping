<?php

$installer = $this;
$installer->startSetup();
// add quote attributes

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_extra_info', "text null default ''");

$installer->endSetup();