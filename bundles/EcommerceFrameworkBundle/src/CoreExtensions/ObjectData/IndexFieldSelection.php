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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData;

class IndexFieldSelection
{
    public ?string $tenant = null;

    public string $field;

    /**
     * @var string|string[]|null
     */
    public string|array|null $preSelect;

    /**
     * @param string|null $tenant
     * @param string $field
     * @param string|string[] $preSelect
     */
    public function __construct(?string $tenant, string $field, array|string|null $preSelect)
    {
        $this->field = $field;
        $this->preSelect = $preSelect;
        $this->tenant = $tenant;
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string|string[] $preSelect
     */
    public function setPreSelect(array|string $preSelect): void
    {
        $this->preSelect = $preSelect;
    }

    /**
     * @return string|string[]|null
     */
    public function getPreSelect(): array|string|null
    {
        return $this->preSelect;
    }

    public function setTenant(string $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): ?string
    {
        return $this->tenant;
    }
}
