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
 * Implemented by data types whose value is derived from other elements and not persisted on the owning object
 * (for example {@see ReverseObjectRelation}, the read-only mirror of a relation stored on the other objects).
 *
 * Reading such a value can be expensive and the object does not keep it, so saving an object does not read it
 * for validation unless the field is mandatory and checked, and the "load all fields" helper skips it.
 */
interface DerivedValueInterface
{
    /**
     * True when the value is derived from other elements and is neither stored on nor loaded together with the
     * owning object.
     */
    public function isValueDerived(): bool;
}
