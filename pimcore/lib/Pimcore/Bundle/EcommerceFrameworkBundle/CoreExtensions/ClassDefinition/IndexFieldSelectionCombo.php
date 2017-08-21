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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Model\Object\ClassDefinition\Data\Select;

class IndexFieldSelectionCombo extends Select
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'indexFieldSelectionCombo';

    public $specificPriceField = false;
    public $showAllFields = false;
    public $considerTenants = false;

    public function __construct()
    {
        $this->setOptions($this->buildOptions());
    }

    protected function buildOptions(): array
    {
        if (!PimcoreEcommerceFrameworkBundle::isInstalled()) {
            return [];
        }

        $indexService = Factory::getInstance()->getIndexService();
        $indexColumns = $indexService->getIndexAttributes(true);

        $options = [];

        foreach ($indexColumns as $c) {
            $options[] = [
                'key' => $c,
                'value' => $c
            ];
        }

        if ($this->getSpecificPriceField()) {
            $options[] = [
                'key' => IProductList::ORDERKEY_PRICE,
                'value' => IProductList::ORDERKEY_PRICE
            ];
        }

        return $options;
    }

    public function setSpecificPriceField($specificPriceField)
    {
        $this->specificPriceField = $specificPriceField;
    }

    public function getSpecificPriceField()
    {
        return $this->specificPriceField;
    }

    public function setShowAllFields($showAllFields)
    {
        $this->showAllFields = $showAllFields;
    }

    public function getShowAllFields()
    {
        return $this->showAllFields;
    }

    public function setConsiderTenants($considerTenants)
    {
        $this->considerTenants = $considerTenants;
    }

    public function getConsiderTenants()
    {
        return $this->considerTenants;
    }
}
