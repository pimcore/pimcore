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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

/**
 * @internal
 */
trait VarExport
{
    protected array $blockedVarsForExport = [];

    public function getBlockedVarsForExport(): array
    {
        return $this->blockedVarsForExport;
    }

    public function resolveBlockedVars(): array
    {
        $defaultBlockedVars = [
            'fieldDefinitionsCache',
            'columnType',
            'queryColumnType',
        ];

        return array_merge($defaultBlockedVars, $this->getBlockedVarsForExport());
    }

    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }

    public function setBlockedVarsForExport(array $vars): static
    {
        $this->blockedVarsForExport = $vars;

        return $this;
    }
}
