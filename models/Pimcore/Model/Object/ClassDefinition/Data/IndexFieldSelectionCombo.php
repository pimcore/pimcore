<?php

namespace Pimcore\Model\Object\ClassDefinition\Data;

class IndexFieldSelectionCombo extends Select {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "indexFieldSelectionCombo";


    public $specificPriceField = false;
    public $showAllFields = false;
    public $considerTenants = false;



    public function __construct() {

        $indexColumns = array();
        try {
            $indexService = \OnlineShop_Framework_Factory::getInstance()->getIndexService();
            $indexColumns = $indexService->getIndexAttributes(true);
        } catch (\Exception $e) {
            \Logger::err($e);
        }

        $options = array();

        foreach ($indexColumns as $c) {
            $options[] = array(
                "key" => $c,
                "value" => $c
            );
        }  

        if($this->getSpecificPriceField()) {
            $options[] = array(
                "key" => \OnlineShop_Framework_IProductList::ORDERKEY_PRICE,
                "value" => \OnlineShop_Framework_IProductList::ORDERKEY_PRICE
            );            
        }

        $this->setOptions($options);
    }

    public function setSpecificPriceField($specificPriceField) {
        $this->specificPriceField = $specificPriceField;
    }

    public function getSpecificPriceField() {
        return $this->specificPriceField;
    }

    public function setShowAllFields($showAllFields) {
        $this->showAllFields = $showAllFields;
    }

    public function getShowAllFields() {
        return $this->showAllFields;
    }

    public function setConsiderTenants($considerTenants) {
        $this->considerTenants = $considerTenants;
    }

    public function getConsiderTenants() {
        return $this->considerTenants;
    }

}
