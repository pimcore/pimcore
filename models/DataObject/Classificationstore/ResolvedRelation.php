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
 * What {@see RelationResolver::resolve()} answers: the elements of a relation-typed key, and
 * whether a descriptor whose target no longer exists was dropped on the way.
 *
 * The dropped flag is what makes the store dirty, so that the next ordinary save rewrites the
 * group's document without the dead descriptor - the treatment
 * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations::load()} gives a
 * relation row whose target is gone.
 *
 * @internal
 */
final class ResolvedRelation
{
    public function __construct(private readonly mixed $data, private readonly bool $droppedTargets)
    {
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function hasDroppedTargets(): bool
    {
        return $this->droppedTargets;
    }
}
