<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CoreExtensions\ClassDefinition;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Model\Object\ClassDefinition\Data\Select;

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

        //TODO
        if(false && \OnlineShop\Plugin::isInstalled()) {
            $indexColumns = array();
            try {
                $indexService = Factory::getInstance()->getIndexService();
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
                    "key" => IProductList::ORDERKEY_PRICE,
                    "value" => IProductList::ORDERKEY_PRICE
                );
            }

            $this->setOptions($options);
        }

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
