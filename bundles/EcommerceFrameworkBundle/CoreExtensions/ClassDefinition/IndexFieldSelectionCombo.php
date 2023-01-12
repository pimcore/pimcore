<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition;

use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\PimcoreEcommerceFrameworkExtension;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Service;

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
        $options = [];

        if (\Pimcore::getContainer()->has(PimcoreEcommerceFrameworkExtension::SERVICE_ID_FACTORY)) {
            try {
                $indexService = Factory::getInstance()->getIndexService();
                $indexColumns = $indexService->getIndexAttributes(true);

                foreach ($indexColumns as $c) {
                    $options[] = [
                        'key' => $c,
                        'value' => $c,
                    ];
                }

                if ($this->getSpecificPriceField()) {
                    $options[] = [
                        'key' => ProductListInterface::ORDERKEY_PRICE,
                        'value' => ProductListInterface::ORDERKEY_PRICE,
                    ];
                }
            } catch (\Exception $e) {
                Logger::error((string) $e);
            }
        }

        return $options;
    }

    /**
     * @param bool $specificPriceField
     * @return void
     */
    public function setSpecificPriceField($specificPriceField)
    {
        $this->specificPriceField = $specificPriceField;
    }

    /**
     * @return bool
     */
    public function getSpecificPriceField()
    {
        return $this->specificPriceField;
    }

    /**
     * @param bool $showAllFields
     * @return void
     */
    public function setShowAllFields($showAllFields)
    {
        $this->showAllFields = $showAllFields;
    }

    /**
     * @return bool
     */
    public function getShowAllFields()
    {
        return $this->showAllFields;
    }

    /**
     * @param bool $considerTenants
     * @return void
     */
    public function setConsiderTenants($considerTenants)
    {
        $this->considerTenants = $considerTenants;
    }

    /**
     * @return bool
     */
    public function getConsiderTenants()
    {
        return $this->considerTenants;
    }

    /**
     * @return $this
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()// : static
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return $this;
    }
}
