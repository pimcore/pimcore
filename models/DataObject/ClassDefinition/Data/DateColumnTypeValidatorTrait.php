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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * Shared by Date, Datetime and DateRange, whose columnType/queryColumnType are not stripped from
 * (de)serialized class-definition data by resolveBlockedVars() and therefore need to be validated
 * before being stored, to prevent them from being concatenated raw into DDL statements.
 *
 * @internal
 */
trait DateColumnTypeValidatorTrait
{
    /**
     * Guards against DDL injection via a crafted columnType/queryColumnType: only a single SQL
     * type declaration, optionally with a size/precision and trailing keyword modifiers (e.g.
     * "bigint(20)", "date", "tinyint(1) unsigned"), is allowed - no additional clauses or
     * statements.
     */
    private static function isValidColumnType(string $columnType): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9]*(\(\s*\d+(\s*,\s*\d+)?\s*\))?(\s+[A-Za-z]+)*$/', trim($columnType));
    }
}
