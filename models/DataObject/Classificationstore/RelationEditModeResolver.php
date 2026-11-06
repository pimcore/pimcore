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

use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;

/**
 * Renders the edit mode value of the relation-typed keys of a classification store from their stored
 * descriptors, without instantiating a target.
 *
 * The payload does not need the elements. Every relation type's `getDataForEditmode()` reads nothing
 * off an element but columns of its own table - its id, its path, its type, its class name, whether
 * it is published and, for the two that render a grid, its key and its two dates - which
 * {@see RelationResolver::summarize()} answers for every target of a whole store in one chunked
 * statement per element type. Resolving the descriptors instead would cost one element load per
 * target, {@see \Pimcore\Model\Element\Service::getElementById()}, and the payload carries every
 * active group of every language: fifty relation attributes would load fifty objects to render one
 * tab.
 *
 * The rendered arrays are the ones the key's own `getDataForEditmode()` produces, down to the order
 * of their members, and that agreement is asserted over one fixture per type rather than asserted
 * here - see `DocumentEditModeRenderingTest`. A relation type this class does not know, which is
 * what a project type is, is reported as unsupported by {@see self::supports()} and resolved into
 * its elements by the caller as before.
 *
 * @internal
 */
final class RelationEditModeResolver
{
    /**
     * The relation types whose edit mode shape this class renders, matched exactly rather than by
     * inheritance: a project type that extends one of them may render its rows differently, and
     * getting its own `getDataForEditmode()` run over resolved elements is the right answer for it
     * even though that costs an element load per target.
     */
    private const RENDERED_TYPES = RelationResolver::CORE_RELATION_TYPES;

    private const TYPE_ASSET = 'asset';

    private const TYPE_DOCUMENT = 'document';

    private const COLUMNS_MEMBER = 'columns';

    private const DATA_MEMBER = 'data';

    public function __construct(private readonly RelationResolver $relationResolver)
    {
    }

    /**
     * Whether this class renders the edit mode value of such a key, which the caller has to ask
     * before handing it one: the five relation types core ships are rendered, {@see self::RENDERED_TYPES},
     * and anything else - a project type, or a core type a project replaced in the data type map -
     * is not.
     */
    public function supports(?Data $fieldDefinition): bool
    {
        return $fieldDefinition !== null
            && in_array($fieldDefinition::class, self::RENDERED_TYPES, true);
    }

    /**
     * The edit mode values of several stored values, against one set of lookups.
     *
     * @param list<RelationRequest> $requests every one of them for a key {@see self::supports()}
     *
     * @return list<RenderedRelation> in the order the requests came in
     *
     * @throws Exception
     */
    public function renderAll(array $requests): array
    {
        $descriptors = [];
        foreach ($requests as $request) {
            foreach ($this->relationResolver->describe($request->getNormalizedValue()) as $descriptor) {
                $descriptors[] = $descriptor;
            }
        }

        $summaries = $this->relationResolver->summarize($descriptors);

        return array_map(
            fn (RelationRequest $request): RenderedRelation => $this->render(
                $request->getFieldDefinition(),
                $request->getNormalizedValue(),
                $summaries
            ),
            $requests
        );
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $summaries
     */
    private function render(Data $fieldDefinition, mixed $normalizedValue, array $summaries): RenderedRelation
    {
        return new RenderedRelation(
            $this->renderData($fieldDefinition, $normalizedValue, $summaries),
            $this->hasDroppedTargets($normalizedValue, $summaries)
        );
    }

    /**
     * Only one of the five {@see self::RENDERED_TYPES} arrives here. The advanced pair is asked
     * before the two types it extends, as the first match wins, and the to-one type is what is
     * left.
     *
     * @param array<string, array<int, array<string, mixed>>> $summaries
     */
    private function renderData(Data $fieldDefinition, mixed $normalizedValue, array $summaries): mixed
    {
        $survivors = $this->survivors($normalizedValue, $summaries);

        if ($fieldDefinition instanceof AdvancedManyToManyObjectRelation) {
            return $this->renderAdvancedObjects($fieldDefinition, $survivors);
        }

        if ($fieldDefinition instanceof AdvancedManyToManyRelation) {
            return $this->renderAdvancedElements($fieldDefinition, $survivors);
        }

        if ($fieldDefinition instanceof ManyToManyObjectRelation) {
            return $this->renderObjects($survivors);
        }

        if ($fieldDefinition instanceof ManyToManyRelation) {
            return $this->renderElements($survivors);
        }

        return $this->renderElement($survivors);
    }

    /**
     * Whether a descriptor of this value names a target that no longer exists, which is read off the
     * lookups rather than off how many entries were rendered: a target that exists but is not of the
     * kind the key renders - a folder under an object relation - is left out of the payload without
     * the stored document being wrong about it.
     *
     * @param array<string, array<int, array<string, mixed>>> $summaries
     */
    private function hasDroppedTargets(mixed $normalizedValue, array $summaries): bool
    {
        foreach ($this->relationResolver->describe($normalizedValue) as $descriptor) {
            if (!isset($summaries[$descriptor['type']][$descriptor['id']])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The entries of this value whose target still exists, paired with its columns and renumbered
     * from zero - which is the position every relation type's `getDataForEditmode()` numbers its
     * rows by, because it is handed the denormalized value a dead descriptor has already been left
     * out of.
     *
     * @param array<string, array<int, array<string, mixed>>> $summaries
     *
     * @return list<array{entry: mixed, summary: array<string, mixed>}>
     */
    private function survivors(mixed $normalizedValue, array $summaries): array
    {
        if (!is_array($normalizedValue)) {
            return [];
        }

        $entries = $this->relationResolver->describeOne($normalizedValue) === null
            ? $normalizedValue
            : [$normalizedValue];

        $survivors = [];

        foreach ($entries as $entry) {
            $descriptor = is_array($entry) ? $this->relationResolver->describeOne($entry) : null;
            $summary = $descriptor === null
                ? null
                : ($summaries[$descriptor['type']][$descriptor['id']] ?? null);

            if ($summary !== null) {
                $survivors[] = ['entry' => $entry, 'summary' => $summary];
            }
        }

        return $survivors;
    }

    /**
     * {@see ManyToOneRelation::getDataForEditmode()}
     *
     * @param list<array{entry: mixed, summary: array<string, mixed>}> $survivors
     *
     * @return array<string, mixed>|null
     */
    private function renderElement(array $survivors): ?array
    {
        if ($survivors === []) {
            return null;
        }

        $summary = $survivors[0]['summary'];

        return [
            'id' => $summary['id'],
            'path' => $summary['fullpath'],
            'subtype' => $summary['subtype'],
            'type' => $summary['type'],
            'published' => $this->isPublished($summary),
        ];
    }

    /**
     * {@see \Pimcore\Model\Element\Service::isPublished()}, which the to-one type describes its
     * target with: it answers `true` for an element kind that carries no published flag of its own -
     * an asset, and an object folder, whose `published` column is not one it maintains - rather than
     * reading that column.
     *
     * @param array<string, mixed> $summary
     */
    private function isPublished(array $summary): bool
    {
        return $this->isObjectFolder($summary) || $summary['published'] === true;
    }

    /**
     * {@see ManyToManyRelation::getDataForEditmode()}
     *
     * @param list<array{entry: mixed, summary: array<string, mixed>}> $survivors
     *
     * @return list<list<mixed>>|null
     */
    private function renderElements(array $survivors): ?array
    {
        $rendered = [];

        foreach ($survivors as $survivor) {
            $rendered[] = $this->renderElementRow($survivor['summary']);
        }

        return $rendered === [] ? null : $rendered;
    }

    /**
     * @param array<string, mixed> $summary
     *
     * @return list<mixed>
     */
    private function renderElementRow(array $summary): array
    {
        if ($summary['type'] === DataObject::OBJECT_TYPE_OBJECT) {
            if ($this->isObjectFolder($summary)) {
                // the three constants a folder is described by, which is what the type answers
                return [
                    $summary['id'],
                    $summary['fullpath'],
                    DataObject::OBJECT_TYPE_OBJECT,
                    DataObject::OBJECT_TYPE_VARIANT,
                    DataObject::OBJECT_TYPE_FOLDER,
                ];
            }

            return [
                $summary['id'],
                $summary['fullpath'],
                DataObject::OBJECT_TYPE_OBJECT,
                $summary['className'],
                $summary['published'],
            ];
        }

        if ($summary['type'] === self::TYPE_ASSET) {
            return [$summary['id'], $summary['fullpath'], self::TYPE_ASSET, $summary['subtype']];
        }

        return [
            $summary['id'],
            $summary['fullpath'],
            self::TYPE_DOCUMENT,
            $summary['subtype'],
            $summary['published'],
        ];
    }

    /**
     * {@see ManyToManyObjectRelation::getDataForEditmode()}, which renders the objects among the
     * targets and leaves a folder out.
     *
     * @param list<array{entry: mixed, summary: array<string, mixed>}> $survivors
     *
     * @return list<array<string, mixed>>
     */
    private function renderObjects(array $survivors): array
    {
        return array_map(
            fn (array $survivor): array => $this->renderGridRow($survivor['summary']),
            $this->concreteObjects($survivors)
        );
    }

    /**
     * {@see AdvancedManyToManyObjectRelation::getDataForEditmode()}
     *
     * @param list<array{entry: mixed, summary: array<string, mixed>}> $survivors
     *
     * @return list<array<string, mixed>>
     */
    private function renderAdvancedObjects(
        AdvancedManyToManyObjectRelation $fieldDefinition,
        array $survivors
    ): array {
        $rendered = [];

        foreach ($this->concreteObjects($survivors) as $position => $survivor) {
            $row = array_merge(
                $this->renderGridRow($survivor['summary']),
                $this->renderMetadataColumns($fieldDefinition, $survivor['entry'])
            );

            $rendered[] = $this->withRowId($row, $position);
        }

        return $rendered;
    }

    /**
     * {@see AdvancedManyToManyRelation::getDataForEditmode()}, whose rows carry the path under
     * `path` and the class name of an object under `subtype`.
     *
     * @param list<array{entry: mixed, summary: array<string, mixed>}> $survivors
     *
     * @return list<array<string, mixed>>|null
     */
    private function renderAdvancedElements(
        AdvancedManyToManyRelation $fieldDefinition,
        array $survivors
    ): ?array {
        if ($survivors === []) {
            return null;
        }

        $rendered = [];

        foreach ($survivors as $position => $survivor) {
            $row = array_merge(
                $this->renderAdvancedElementRow($survivor['summary']),
                $this->renderMetadataColumns($fieldDefinition, $survivor['entry'])
            );

            $rendered[] = $this->withRowId($row, $position);
        }

        return $rendered;
    }

    /**
     * The survivors whose target is an object or a variant, renumbered from zero: the two types that
     * render objects are handed a value a folder has already been left out of, so a folder must not
     * take a row number with it either.
     *
     * @param list<array{entry: mixed, summary: array<string, mixed>}> $survivors
     *
     * @return list<array{entry: mixed, summary: array<string, mixed>}>
     */
    private function concreteObjects(array $survivors): array
    {
        return array_values(array_filter(
            $survivors,
            fn (array $survivor): bool => $this->isConcreteObject($survivor['summary'])
        ));
    }

    /**
     * @param array<string, mixed> $summary
     *
     * @return array<string, mixed>
     */
    private function renderAdvancedElementRow(array $summary): array
    {
        $row = [
            'id' => $summary['id'],
            'path' => $summary['fullpath'],
            'type' => $summary['type'],
            'subtype' => $summary['subtype'],
        ];

        if ($summary['type'] === self::TYPE_ASSET) {
            return $row;
        }

        if ($summary['type'] === DataObject::OBJECT_TYPE_OBJECT) {
            if ($this->isObjectFolder($summary)) {
                return $row;
            }

            $row['subtype'] = $summary['className'];
        }

        $row['published'] = $summary['published'];

        return $row;
    }

    /**
     * The row id the two advanced types address an edit mode row by, built from the position the
     * row has in the value rather than from the position its descriptor has in the document.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function withRowId(array $row, int $position): array
    {
        $row['rowId'] = $row['id'] . AbstractRelations::RELATION_ID_SEPARATOR . ($position + 1)
            . AbstractRelations::RELATION_ID_SEPARATOR . $row['type'];

        return $row;
    }

    /**
     * The per-relation column values of one entry of an advanced relation, read the way the metadata
     * item answers for them: the column names the item carries are matched case insensitively, and a
     * column of the definition the item does not carry is left out of the row rather than rendered
     * as null - which is what the type does, where the item throws for it and the throw is logged.
     *
     * @return array<string, mixed>
     */
    private function renderMetadataColumns(
        AdvancedManyToManyObjectRelation|AdvancedManyToManyRelation $fieldDefinition,
        mixed $entry
    ): array {
        $declared = is_array($entry) && is_array($entry[self::COLUMNS_MEMBER] ?? null)
            ? $entry[self::COLUMNS_MEMBER]
            : [];
        $data = is_array($entry) && is_array($entry[self::DATA_MEMBER] ?? null)
            ? $entry[self::DATA_MEMBER]
            : [];

        $columns = [];

        foreach ($fieldDefinition->getColumns() as $column) {
            $index = array_searchi((string) $column['key'], $declared);

            if ($index === false) {
                continue;
            }

            $columns[(string) $column['key']] = $data[$declared[$index]] ?? null;
        }

        return $columns;
    }

    /**
     * {@see \Pimcore\Model\Element\Service::gridElementData()}, which is what the two types that
     * render a grid describe a target by.
     *
     * @param array<string, mixed> $summary
     *
     * @return array<string, mixed>
     */
    private function renderGridRow(array $summary): array
    {
        return [
            'id' => $summary['id'],
            'fullpath' => $summary['fullpath'],
            'type' => $summary['type'],
            'subtype' => $summary['subtype'],
            'filename' => $summary['filename'],
            'creationDate' => $summary['creationDate'],
            'modificationDate' => $summary['modificationDate'],
            'published' => $summary['published'],
        ];
    }

    /**
     * Whether the target is one {@see \Pimcore\Model\DataObject\Concrete} stands for: an object or a
     * variant, which carry their own data, and not a folder, which does not.
     *
     * @param array<string, mixed> $summary
     */
    private function isConcreteObject(array $summary): bool
    {
        return $summary['type'] === DataObject::OBJECT_TYPE_OBJECT && !$this->isObjectFolder($summary);
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function isObjectFolder(array $summary): bool
    {
        return $summary['type'] === DataObject::OBJECT_TYPE_OBJECT
            && $summary['subtype'] === DataObject::OBJECT_TYPE_FOLDER;
    }
}
