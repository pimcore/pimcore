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
 * What {@see RelationEditModeResolver::renderAll()} answers: the edit mode value of a relation-typed
 * key, rendered from the stored descriptors rather than from the elements they address, and whether
 * a descriptor whose target no longer exists was left out on the way.
 *
 * It wraps what the key's own `getDataForEditmode()` would have answered, so that
 * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::getDataForEditmode()}
 * can tell a value it still has to render from one that is already rendered.
 *
 * The dropped flag says that a descriptor of the value names a target that no longer exists. The
 * caller answers that by resolving the key the ordinary way instead of using this rendering, which
 * is what takes the dead descriptor out of the stored value and marks the store dirty - the
 * treatment a read through {@see ResolvedRelation} gives it.
 *
 * @internal
 */
final class RenderedRelation
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

    /**
     * Whether the rendered value counts as empty, which is the question
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data::isEmpty()} would have been asked about
     * the elements: a rendered value is null or carries one entry per target that was rendered, so
     * the two answers are the same.
     */
    public function isEmpty(): bool
    {
        return $this->data === null || $this->data === [];
    }
}
