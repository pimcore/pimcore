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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

/**
 * @internal
 */
trait VarExport
{
    /**
     * @var array
     */
    protected $blockedVarsForExport = [];

    /**
     * @return array
     */
    public function getBlockedVarsForExport(): array
    {
        return $this->blockedVarsForExport;
    }

    /**
     * @return array
     */
    public function resolveBlockedVars(): array
    {
        $defaultBlockedVars = [
            'fieldDefinitionsCache',
            'columnType',
            'queryColumnType',
        ];

        return array_merge($defaultBlockedVars, $this->getBlockedVarsForExport());
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }
}
