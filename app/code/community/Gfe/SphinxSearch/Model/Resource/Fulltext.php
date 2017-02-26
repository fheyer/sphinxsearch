<?php

/**
 * SphinxSearch Fulltext Index resource model
 *
 * @category   Gfe
 * @package    Gfe_SphinxSearch
 * @author     GFE Media <florian.heyer@gfe-media.de>
 */
class Gfe_SphinxSearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{
	
    /**
     * Init resource model
     *
     */
    protected function _construct()
    {
		// engine is only important fpr indexing
		if (! Mage::getStoreConfigFlag('sphinxsearch/active/indexer')) {
			return parent::_construct();
		}		
		
        $this->_init('catalogsearch/fulltext', 'product_id');
        $this->_engine = Mage::helper('sphinxsearch')->getEngine();
    }	
	
    /**
     * Prepare results for query
     *
     * @param Mage_CatalogSearch_Model_Fulltext $object
     * @param string $queryText
     * @param Mage_CatalogSearch_Model_Query $query
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext
     */
	public function prepareResult($object, $queryText, $query)
	{
		if (! Mage::getStoreConfigFlag('sphinxsearch/active/frontend')) {
			return parent::prepareResult($object, $queryText, $query);
		}

		$sphinx = Mage::helper('sphinxsearch')->getSphinxAdapter();
		$foundData = array();

		$index = Mage::getStoreConfig('sphinxsearch/server/index');

		if (empty($index)) {
			$sphinx->addQuery($queryText);
		} else {
			$sphinx->addQuery($queryText, $index);
		}

		$results = $sphinx->runQueries();
		unset($sphinx); $sphinx=null;

		// Loop through our Sphinx results
		if ($results !== false) {
			$resultTable = $this->getTable('catalogsearch/result');
			foreach ($results as $item)
			{
				if (empty($item['matches']))
					continue;

				foreach ($item['matches'] as $doc => $docinfo)
				{
					// Ensure we log query results into the Magento table.
					$weight = $docinfo['weight']/1000;
					$foundData[$doc] = $weight;
					$sql = sprintf("INSERT INTO `%s` "
						. " (`query_id`, `product_id`, `relevance`) VALUES "
						. " (%d, %d, %f) "
						. " ON DUPLICATE KEY UPDATE `relevance` = %f",
						$resultTable,
						$query->getId(),
						$doc,
						$weight,
						$weight
					);
					try {
						$this->_getWriteAdapter()->query($sql);
					} catch (Zend_Db_Statement_Exception $e) {
						/*
						 * if the sphinx index is out of date and returns
						 * product ids which are no longer in the database
						 * integrity contraint exceptions are thrown.
						 * we catch them here and simply skip them.
						 * all other exceptions are forwarded
						 */
						$message = $e->getMessage();
						if (strpos($message, 'SQLSTATE[23000]: Integrity constraint violation') === FALSE) {
							throw $e;
						}
					}
				}
			}
		}
		/**
		 * Check for the existence of _foundData property for backwards
		 * compatibility with Magento versions < 1.9.3.1.
		 */
		if(property_exists($this, '_foundData')){
			$this->_foundData = $foundData;
		}
		$query->setIsProcessed(1);
		return $this;
	}
	
    /**
     * Prepare Fulltext index value for product
     *
     * @param array $indexData
     * @param array $productData
     * @param int $storeId
     * @return string
     */
    protected function _prepareProductIndex($indexData, $productData, $storeId)
    {
		if (! Mage::getStoreConfigFlag('sphinxsearch/active/indexer')) {
			return parent::_prepareProductIndex($indexData, $productData, $storeId);
		}		
		
        $index = array();

        foreach ($this->_getSearchableAttributes('static') as $attribute) {
            $attributeCode = $attribute->getAttributeCode();

            if (isset($productData[$attributeCode])) {
                $value = $this->_getAttributeValue($attribute->getId(), $productData[$attributeCode], $storeId);
                if ($value) {
                    //For grouped products
                    if (isset($index[$attributeCode])) {
                        if (!is_array($index[$attributeCode])) {
                            $index[$attributeCode] = array($index[$attributeCode]);
                        }
                        $index[$attributeCode][] = $value;
                    }
                    //For other types of products
                    else {
                        $index[$attributeCode] = $value;
                    }
                }
            }
        }

        foreach ($indexData as $entityId => $attributeData) {
            foreach ($attributeData as $attributeId => $attributeValue) {
                $value = $this->_getAttributeValue($attributeId, $attributeValue, $storeId);
                if (!is_null($value) && $value !== false) {
                    $attributeCode = $this->_getSearchableAttribute($attributeId)->getAttributeCode();

                    if (isset($index[$attributeCode])) {
                        $index[$attributeCode][$entityId] = $value;
                    } else {
                        $index[$attributeCode] = array($entityId => $value);
                    }
                }
            }
        }

        if (!$this->_engine->allowAdvancedIndex()) {
            $product = $this->_getProductEmulator()
                ->setId($productData['entity_id'])
                ->setTypeId($productData['type_id'])
                ->setStoreId($storeId);
            $typeInstance = $this->_getProductTypeInstance($productData['type_id']);
            if ($data = $typeInstance->getSearchableData($product)) {
                $index['options'] = $data;
            }
        }

        if (isset($productData['in_stock'])) {
            $index['in_stock'] = $productData['in_stock'];
        }
        
        $categories = array();
        if ($productData['entity_id']) {
        	foreach (Mage::getModel('catalog/product')->load((int) $productData['entity_id'])->getCategoryCollection()->setStoreId($storeId)->addNameToResult() as $item) {
        		$categories[] = $item->getName();
        	}
        }
        $index['categories'] = $categories;

        return $this->_engine->prepareEntityIndex($index, $this->_separator, $productData['entity_id']);
    }	
	
}
