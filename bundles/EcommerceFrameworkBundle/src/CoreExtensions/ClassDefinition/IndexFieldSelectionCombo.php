<?php
declare(strict_types=1);

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
    public string $fieldtype = 'indexFieldSelectionCombo';

    public bool $specificPriceField = false;

    public bool $showAllFields = false;

    public bool $considerTenants = false;

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

    public function setSpecificPriceField(bool $specificPriceField): void
    {
        $this->specificPriceField = $specificPriceField;
    }

    public function getSpecificPriceField(): bool
    {
        return $this->specificPriceField;
    }

    public function setShowAllFields(bool $showAllFields): void
    {
        $this->showAllFields = $showAllFields;
    }

    public function getShowAllFields(): bool
    {
        return $this->showAllFields;
    }

    public function setConsiderTenants(bool $considerTenants): void
    {
        $this->considerTenants = $considerTenants;
    }

    public function getConsiderTenants(): bool
    {
        return $this->considerTenants;
    }

    public function jsonSerialize(): mixed
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return parent::jsonSerialize();
    }
}
