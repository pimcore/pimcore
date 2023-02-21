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

use Pimcore\Model\DataObject\ClassDefinition\Data\Textarea;

class IndexFieldSelectionField extends Textarea
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public string $fieldtype = 'indexFieldSelectionField';

    public bool $specificPriceField = false;

    public bool $showAllFields = false;

    public bool $considerTenants = false;

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

    public function isEmpty(mixed $data): bool
    {
        if (is_string($data)) {
            return strlen($data) < 1;
        }
        if (is_array($data)) {
            return empty($data);
        }

        return true;
    }

    /**
     * @param mixed $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getDataFromEditmode(mixed $data, $object = null, array $params = []): string
    {
        if (is_array($data)) {
            $data = implode(',', $data);
        }

        return $data;
    }
}
