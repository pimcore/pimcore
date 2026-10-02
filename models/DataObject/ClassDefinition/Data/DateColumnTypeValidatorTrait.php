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
     * Guards against DDL injection via a crafted columnType/queryColumnType: only an allowlisted
     * integer/date/time SQL type, optionally with a size/precision and "unsigned"/"zerofill" modifiers
     * (e.g. "bigint(20)", "date", "tinyint(1) unsigned", "bigint(20) zerofill"), is allowed - no other keywords, clauses
     * (e.g. "UNIQUE", "NOT NULL") or statements.
     */
    private static function isValidColumnType(string $columnType): bool
    {
        return (bool) preg_match(
            '/^(tinyint|smallint|mediumint|int|integer|bigint|date|datetime|timestamp|time|year)(\(\s*\d+(\s*,\s*\d+)?\s*\))?(\s+unsigned)?(\s+zerofill)?$/i',
            trim($columnType)
        );
    }
}
