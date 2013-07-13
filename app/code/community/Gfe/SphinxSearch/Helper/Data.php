<?php

class Gfe_SphinxSearch_Helper_Data extends Mage_Core_Helper_Abstract {
    
    public function getSphinxAdapter() {
        require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'sphinxapi.php');

        // Connect to our Sphinx Search Engine and run our queries
        $sphinx = new SphinxClient();
		
		$host = Mage::getStoreConfigFlag('sphinxsearch/active/host');
		$port = Mage::getStoreConfigFlag('sphinxsearch/active/port');
		if (empty($host)) {
			return $sphinx;
		}
		if (empty($port)) {
			$port = 9312;			
        }		
        $sphinx->SetServer($host, $port);
        $sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
        $sphinx->setFieldWeights(array(
            'name' => 7,
            //'category' => 1,
            //'name_attributes' => 1
            'data_index' => 3
        ));
        $sphinx->setLimits(0, 200, 1000, 5000);

        // SPH_RANK_PROXIMITY_BM25 ist default
        $sphinx->SetRankingMode(SPH_RANK_SPH04, ""); // 2nd parameter is rank expr?
        
        return $sphinx;
    }

	/**
	 * taken from https://gist.github.com/2727341
	 */
    public function prepareIndexdata($index, $separator = ' ', $entity_id = NULL)
    {
            $_attributes = array();

            $_index = array();
            foreach ($index as $key => $value) {

                    // As long as this isn't a standard attribute use it in our 
                    // concatenated column.
                    if ( ! in_array($key, array('sku', 'name', 'description', 'short_description', 'meta_keywords', 'meta_title')))
                    {
                            $_attributes[$key] = $value;
                    }

                    if (!is_array($value)) {
                            $_index[] = $value;
                    }
                    else {
                            $_index = array_merge($_index, $value);
                    }
            }

            // Get the product name.
            if (is_array($index['name']))
            {
                    $name = $index['name'][0]; // Use the configurable product's name
            }
            else
            {
                    $name = $index['name']; // Use the simple product's name
            }

            // Combine the name with each non-standard attribute
            $name_attributes = array();
            foreach ($_attributes as $code => $value)
            {
                    if ( ! is_array($value))
                    {
                            $value = array($value);
                    }

                    // Loop through each simple product's attribute values and assign to 
                    // product name.
                    foreach ($value as $key => $item_value)
                    {
                            if (isset($name_attributes[$key]))
                            {
                                    $name_attributes[$key] .= ' '.$item_value;
                            }
                            else
                            {
                                    // The first time we see this add the name to start.
                                    $name_attributes[] = $name.' '.$item_value;
                            }
                    }
            }

            $category = '';
            if ($entity_id)
            {
                    $entity_id = (int) $entity_id;
                    $read	  = Mage::getSingleton('core/resource')->getConnection('core/read');

                    // Get categories
                    $data = $read->fetchRow("
                            SELECT value FROM `catalog_category_entity_varchar` `ccev`
                                    JOIN `catalog_category_entity` `cce` ON `cce`.`entity_id` = `ccev`.`entity_id`
                                    JOIN `catalog_category_product` `ccp` on `ccp`.`category_id` = `cce`.`entity_id`
                            WHERE `ccp`.`product_id` = {$entity_id}
                                    AND `ccev`.`attribute_id` = 33
                            ORDER BY `cce`.`level` DESC 
                            LIMIT 1"
                    );

                    $category = $data['value'];
            }

            $data = array(
                    'name'			=> $name,
                    'name_attributes' => join('. ', $name_attributes),
                    'data_index'	  => join($separator, $_index),
                    'category'		=> $category,
            );

            return $data;
    }	
	
	public function getEngine() {
		return Mage::getResourceSingleton('sphinxsearch/fulltext_engine');
	}
    
}

?>