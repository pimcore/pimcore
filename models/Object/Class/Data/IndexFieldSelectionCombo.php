<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_IndexFieldSelectionCombo extends Object_Class_Data_Select {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "indexFieldSelectionCombo";


    public $specificPriceField = false;
    public $showAllFields = false;



    public function __construct() {

        $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
        $indexColumns = $indexService->getIndexColumns(true);

        $options = array();

        foreach ($indexColumns as $c) {
            $options[] = array(
                "key" => $c,
                "value" => $c
            );
        }  

//        var_dump($this->getSpecificPriceField()); die();
        if($this->getSpecificPriceField()) {
            $options[] = array(
                "key" => OnlineShop_Framework_ProductList::ORDERKEY_PRICE,
                "value" => OnlineShop_Framework_ProductList::ORDERKEY_PRICE
            );            
        }

//        p_r($options); die();
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


}
