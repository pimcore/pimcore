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

namespace Pimcore\Model\DataObject\Classificationstore;

/**
 * The stored value of a relation-typed key that has not been resolved into its elements yet.
 *
 * It wraps what {@see \Pimcore\Normalizer\NormalizerInterface::normalize()} produced, so that the
 * readers of {@see Classificationstore::getItems()} can tell a descriptor from a value that is
 * already a relation's elements without guessing from the shape of an array.
 *
 * @internal
 */
final class RelationDescriptor
{
    public function __construct(private readonly mixed $value)
    {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
