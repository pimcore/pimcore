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
    /**
     * The member an advanced relation nests the target under, where the plain relation types carry
     * it in the entry itself ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation::normalize()}).
     */
    private const ELEMENT_MEMBER = 'element';

    public function __construct(private readonly mixed $value)
    {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * The target one entry of a normalized relation value names, and null for an entry that names
     * none.
     *
     * What one stored entry of a relation-typed key looks like, in one place: a caller tells a
     * to-one value - a single descriptor - from a to-many one by asking this of the value itself
     * ({@see RelationResolver::describe()}), and a caller that has to decide whether a relation type
     * can read a stored value at all asks it of every entry
     * ({@see Service::canReadStoredValue()}). The two cannot come apart about the shape, which is
     * what matters for the second one: it is the precondition of the code that reads such an entry,
     * `$elementData['type']`, and an entry of any other shape is what a key whose inner type was
     * changed over stored data leaves behind.
     *
     * @param array<array-key, mixed> $entry
     *
     * @return array{type: string, id: int}|null
     */
    /**
     * Whether this entry carries its target under a member of its own rather than in the entry
     * itself, which is what an ADVANCED relation writes and what tells its stored form from a plain
     * relation's.
     *
     * Asked beside {@see self::describeOne()} rather than inside it: that one answers "which target"
     * for an entry of either shape, which is what its callers need - the dependency and cache-tag
     * walks, the target-existence lookups and the edit mode rendering all run over the stored values
     * of plain and advanced keys alike. Whether the SHAPE is the one the definition in force reads is
     * a different question, and only {@see Service::canReadStoredValue()} asks it.
     *
     * @param array<array-key, mixed> $entry
     */
    public static function nestsItsTarget(array $entry): bool
    {
        return array_key_exists(self::ELEMENT_MEMBER, $entry);
    }

    public static function describeOne(array $entry): ?array
    {
        $element = $entry[self::ELEMENT_MEMBER] ?? $entry;

        if (!is_array($element) || !isset($element['type'], $element['id'])) {
            return null;
        }

        if (!is_string($element['type']) || !is_scalar($element['id'])) {
            return null;
        }

        return ['type' => $element['type'], 'id' => (int) $element['id']];
    }
}
