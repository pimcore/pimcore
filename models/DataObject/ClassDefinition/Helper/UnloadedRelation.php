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

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionItem;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickItem;

/**
 * Judges a lazy loaded relation that was not loaded by its stored relation rows, without loading every related
 * element: whether it passes the checks of checkValidity() that do not need the related elements (mandatory, max.
 * items), and which dependencies it resolves to. Only the relation types of the core qualify, a custom type or a
 * subclass may validate a relation or resolve its dependencies differently.
 *
 * The container is the one that stores the relation rows: the object, a field collection item, an object brick or
 * the localized fields, the latter with the language and context in $params like for AbstractRelations::load().
 * The caller decides which value the rows stand for, the one of a getter or the stored one:
 * - $mayInherit: whether an empty relation takes the value of the parent, then the rows of the container cannot tell
 * - $withUnpublished: whether unpublished elements are kept, like in the stored value of localized fields, or left
 *   out while they are hidden, like a getter does
 *
 * @internal
 */
final class UnloadedRelation
{
    /**
     * If the stored rows are not conclusive, the relation has to be loaded and validated as usual.
     */
    public static function isValid(
        AbstractRelations $field,
        Concrete|FieldcollectionItem|ObjectbrickItem|Localizedfield $container,
        array $params,
        bool $checkMandatory,
        bool $mayInherit,
        bool $withUnpublished
    ): bool {
        if (!self::isCoreRelationType($field)) {
            return false;
        }

        if (!$field instanceof ManyToManyRelation && !$field instanceof ManyToManyObjectRelation) {
            // a many-to-one relation has no max. items, its mandatory check loads the single element
            return !$checkMandatory;
        }

        $maxItems = $field->getMaxItems() ?? 0;
        if (!$checkMandatory && $maxItems <= 0) {
            return true;
        }

        $count = count(self::describe($field, $container, $params, $withUnpublished));
        if ($count === 0 && ($checkMandatory || $mayInherit)) {
            // without own relations the value may be inherited from the parent, and its number of relations too
            return false;
        }

        return $maxItems <= 0 || $count <= $maxItems;
    }

    /**
     * The dependencies the loaded relation resolves to. Returns null if the rows cannot tell, then the relation has
     * to be loaded.
     *
     * @return array<string, array{id: int, type: string}>|null
     */
    public static function resolveDependencies(
        AbstractRelations $field,
        Concrete|FieldcollectionItem|ObjectbrickItem|Localizedfield $container,
        array $params,
        bool $mayInherit,
        bool $withUnpublished
    ): ?array {
        if (!self::isCoreRelationType($field)
            || (!$field instanceof ManyToManyRelation && !$field instanceof ManyToManyObjectRelation)) {
            // a many-to-one relation loads a single element only
            return null;
        }

        $elements = self::describe($field, $container, $params, $withUnpublished);
        if ($elements === [] && $mayInherit) {
            // without own relations the getter returns the relations of the parent
            return null;
        }

        $dependencies = [];
        foreach ($elements as $element) {
            $dependencies[$element['type'] . '_' . $element['id']] = [
                'id' => $element['id'],
                'type' => $element['type'],
            ];
        }

        return $dependencies;
    }

    /**
     * The elements the stored relation rows refer to, the way the loaded relation holds them: elements that no
     * longer exist and folders in an object relation are left out, and unpublished ones unless they are kept.
     *
     * @return list<array{id: int, parentId: int, type: string, subtype: string, className: string|null, fullpath: string, filename: string, published: bool}>
     */
    private static function describe(
        ManyToManyRelation|ManyToManyObjectRelation $field,
        Concrete|FieldcollectionItem|ObjectbrickItem|Localizedfield $container,
        array $params,
        bool $withUnpublished
    ): array {
        $hideUnpublished = DataObject::doHideUnpublished();
        if ($withUnpublished) {
            DataObject::setHideUnpublished(false);
        }

        try {
            $summaries = $field->loadRelationSummaries($container, $params);
        } finally {
            DataObject::setHideUnpublished($hideUnpublished);
        }

        if (!$field instanceof ManyToManyObjectRelation) {
            return $summaries;
        }

        // an object relation only holds concrete objects
        return array_values(array_filter(
            $summaries,
            static fn (array $summary) => $summary['subtype'] !== 'folder'
        ));
    }

    /**
     * Whether the field is one of the relation types of the core, the only ones whose rows are judged here.
     */
    public static function isCoreRelationType(AbstractRelations $field): bool
    {
        return in_array($field::class, [
            ManyToOneRelation::class,
            ManyToManyRelation::class,
            ManyToManyObjectRelation::class,
            AdvancedManyToManyRelation::class,
            AdvancedManyToManyObjectRelation::class,
        ], true);
    }
}
