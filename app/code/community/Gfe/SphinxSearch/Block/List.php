<?php

/**
 * This is some experimental code
 * You can use this Block to include a list of products e.g. on a 404 error page
 * The products are searched by the url which led to the 404 page
 * 
 * Break calling URL into keywords and try to find products with these keywords
 * 
 */
class Gfe_SphinxSearch_Block_List extends Mage_Catalog_Block_Product_List {

    protected function _construct() {
        parent::_construct();

        // set defaults
        $this->setSortBy('relevance');
        $this->setDefaultDirection('desc');
    }

    public function filterWords($word) {
        return ($word != 'html');
    }

    /**
     * search products with url words
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getProductCollection() {
        if (is_null($this->_productCollection)) {
            $speakingPath = $this->getRequest()->getRequestString();
            $words = preg_split('/[-_\/.]+/', $speakingPath, null, PREG_SPLIT_NO_EMPTY);
            $words = array_filter($words, array($this, 'filterWords'));

            $sphinxServer = Mage::helper('sphinxsearch')->getSphinxAdapter();

            $index = Mage::getStoreConfig('sphinxsearch/server/index');
            if (empty($index)) {
                $sphinxServer->AddQuery(implode(' ', $words));
            } else {
                $sphinxServer->AddQuery(implode(' ', $words), $index);
            }

            $results = $sphinxServer->RunQueries();

            $productIds = array();
            // Loop through our Sphinx results
            $collection = Mage::getResourceModel('catalog/product_collection');
            $collection->addStoreFilter();

            if ($results !== false) {
                foreach ($results as $item) {
                    if (empty($item['matches']))
                        continue;

                    foreach ($item['matches'] as $productId => $docinfo) {
                        // Ensure we log query results into the Magento table.
                        $productIds[] = $productId;
                    }
                }
            }
            if (count($productIds)) {
                $collection->addIdFilter($productIds);
                //$collection->getSelect()->distinct(true)->order('rand()');    
                //$numProducts = $this->getNumProducts() ? $this->getNumProducts() : 0;
                //$collection->setPage(1, $numProducts);
                //$this->setIsRandom(true);
            } else {
                // this is just a hack to get an empty collection
                $collection->addIdFilter('thisisdefinitlynoprodid');
            }

            $this->prepareProductCollection($collection);
            $this->_productCollection = $collection;
        }

        /*
         * set default search params via url so that toolbar-block
         * uses them for the query
         * Sortierung ist nur uebergangsloesung, eigentlich sollten die Artikel
         * nach Relevanz von sphinx sortiert sein, aber bei obigem Aufbau der
         * Collection geht die Relevanz verloren
         */
        Mage::app()->getRequest()->setParam('order', 'name');
        Mage::app()->getRequest()->setParam('dir', 'asc');
        Mage::app()->getRequest()->setParam('limit', '30');

        return $this->_productCollection;
    }

    /**
     * Taken from Mage_Catalog_Model_Layer
     * @param type $collection
     * @return \Gfe_SphinxSearch_Block_List
     */
    private function prepareProductCollection($collection) {
        $attributes = Mage::getSingleton('catalog/config')
                ->getProductAttributes();
        $collection->addAttributeToSelect($attributes)
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
    }

    /**
     * We don't need a toolbar...
     * @return string
     */
    public function getToolbarHtml() {
        return '';
    }

}

?>
