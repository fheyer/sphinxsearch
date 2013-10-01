<?php

/**
 * SphinxSearch Fulltext Index Engine resource model
 *
 * @category   Gfe
 * @package    Gfe_SphinxSearch
 * @author     GFE Media <florian.heyer@gfe-media.de>
 */
class Gfe_SphinxSearch_Model_Resource_Fulltext_Engine extends Mage_CatalogSearch_Model_Resource_Fulltext_Engine
{
    /**
     * Multi add entities data to fulltext search table
     *
     * @param int $storeId
     * @param array $entityIndexes
     * @param string $entity 'product'|'cms'
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext_Engine
     */
    public function saveEntityIndexes($storeId, $entityIndexes, $entity = 'product')
    {
            $adapter = $this->_getWriteAdapter();
            $data	= array();
            $storeId = (int)$storeId;
            foreach ($entityIndexes as $entityId => &$index) {
                    $data[] = array(
                            'product_id'      => (int)$entityId,
                            'store_id'        => $storeId,
                            'data_index'      => $index['data_index'],
                            'name'            => $index['name'],
                            'name_attributes' => $index['name_attributes'],
                            'category'        => $index['category'],
                    );
            }

            if ($data) {
                    $adapter->insertOnDuplicate('sphinx_catalogsearch_fulltext', $data, array('data_index', 'name', 'name_attributes', 'category'));
            }

            return $this;
    }

    /**
     * Remove entity data from fulltext search table
     *
     * @param int $storeId
     * @param int $entityId
     * @param string $entity 'product'|'cms'
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext_Engine
     */
    public function cleanIndex($storeId = null, $entityId = null, $entity = 'product')
    {
        $where = array();

        if (!is_null($storeId)) {
            $where[] = $this->_getWriteAdapter()->quoteInto('store_id=?', $storeId);
        }
        if (!is_null($entityId)) {
            $where[] = $this->_getWriteAdapter()->quoteInto('product_id IN(?)', $entityId);
        }

        $this->_getWriteAdapter()->delete('sphinx_catalogsearch_fulltext', join(' AND ', $where));

        return $this;
    }

    /**
     * Prepare index array as a string glued by separator
     *
     * @param array $index
     * @param string $separator
     * @return string
     */
    public function prepareEntityIndex($index, $separator = ' ', $entity_id = NULL)
    {
        return Mage::helper('sphinxsearch')->prepareIndexdata($index, $separator, $entity_id);		
    }
}
