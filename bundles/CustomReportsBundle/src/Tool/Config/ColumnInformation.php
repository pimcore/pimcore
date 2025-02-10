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

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Config;

use JsonSerializable;

readonly class ColumnInformation implements JsonSerializable
{
    public function __construct(
        private string $name,
        private bool $disableOrderBy = false,
        private bool $disableFilterable = false,
        private bool $disableDropdownFilterable = false,
        private bool $disableLabel = false
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDisableOrderBy(): bool
    {
        return $this->disableOrderBy;
    }

    public function isDisableFilterable(): bool
    {
        return $this->disableFilterable;
    }

    public function isDisableDropdownFilterable(): bool
    {
        return $this->disableDropdownFilterable;
    }

    public function isDisableLabel(): bool
    {
        return $this->disableLabel;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'disableOrderBy' => $this->disableOrderBy,
            'disableFilterable' => $this->disableFilterable,
            'disableDropdownFilterable' => $this->disableDropdownFilterable,
            'disableLabel' => $this->disableLabel,
        ];
    }
}
