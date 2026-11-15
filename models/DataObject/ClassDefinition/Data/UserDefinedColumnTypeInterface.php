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
 * Marks field types whose columnType/queryColumnType is writable through a public setter
 * (e.g. via the class editor or a programmatically built class definition) rather than being
 * computed entirely from internal, already-validated state. Helper\Dao::addModifyColumn()
 * validates the type string for these fields only, since that allowlist is not guaranteed to
 * match every built-in or custom field type's column type.
 *
 * @internal this is an implementation detail of the DDL-injection guard in Helper\Dao, not a
 *           supported extension point - do not implement it on custom field types.
 */
interface UserDefinedColumnTypeInterface
{
}
