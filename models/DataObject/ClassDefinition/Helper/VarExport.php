<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
