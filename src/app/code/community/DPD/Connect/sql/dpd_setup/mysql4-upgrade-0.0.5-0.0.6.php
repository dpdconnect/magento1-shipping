<?php

$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'dpd_label_exists', "bool null default 0");
$installer->endSetup();