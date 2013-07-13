<?php

$installer = $this;
/* $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("	

CREATE TABLE IF NOT EXISTS {$this->getTable('sphinx_catalogsearch_fulltext')} (
  `product_id` int(10) unsigned NOT NULL,
  `store_id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_attributes` longtext NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `data_index` longtext NOT NULL,
  PRIMARY KEY (`product_id`,`store_id`),
  FULLTEXT KEY `data_index` (`data_index`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

");

$installer->endSetup();