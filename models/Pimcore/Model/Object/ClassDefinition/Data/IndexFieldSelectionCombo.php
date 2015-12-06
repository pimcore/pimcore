<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


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
            $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
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
