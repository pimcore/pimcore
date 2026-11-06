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

namespace Pimcore\Contracts\ChangeControl;

use InvalidArgumentException;

/**
 * What a branch records for one data object: an existing one by its id, or a new one with its class,
 * parent and key. It names only the values it changes; a value of null removes what the object holds
 * there.
 *
 * A value is what the field's setter takes, `$object->set<Field>()` (a string for an input, a Carbon
 * date for a date, a QuantityValue for a quantity value), except relations: they name their targets
 * by id, an `ElementRef` for a one-to-one relation and a list of them for a many relation.
 *
 * Immutable: each `with…()` returns a new change. Setting the same place twice keeps the later value.
 * An object given as a value is held as it is, not copied: changing it afterwards changes what every
 * change holding it records, so give each value its own object.
 *
 * ```php
 * $change = DataObjectChange::of(42)
 *     ->withValue('name', 'Chair')
 *     ->withLocalizedValue('description', 'en', 'A chair')
 *     ->withBrickValue('dimensions', 'Size', 'width', 40)
 *     ->withClassificationValue('attributes', 'Look', 'color', 'Red')
 *     ->withValue('categories', [ElementRef::object(7)])
 *     ->withValue('subtitle', null);
 * ```
 */
final readonly class DataObjectChange
{
    /**
     * @param array<string, FieldValue> $values by place
     */
    private function __construct(
        public ?int $id,
        public ?NewDataObject $create,
        private array $values = [],
    ) {
    }

    /** a change to the existing data object with this id */
    public static function of(int $id): self
    {
        if ($id < 1) {
            throw new InvalidArgumentException(sprintf('A data object has a positive id, not %d.', $id));
        }

        return new self($id, null);
    }

    /**
     * A data object the merge creates.
     *
     * @param string $token the consumer's name for it within the branch: letters, digits, `.`, `_` and `-`, at most 80
     * @param int|string $parent an existing object's id, or the token of another object the branch creates
     */
    public static function create(string $token, string $className, int|string $parent, string $key): self
    {
        return new self(null, new NewDataObject($token, $className, $parent, $key));
    }

    /** a field of the class; not a localized field, a brick or a classification store, which have their own */
    public function withValue(string $field, mixed $value): self
    {
        return $this->with(FieldValue::field($field, $value));
    }

    /** a localized field in one language */
    public function withLocalizedValue(string $field, string $language, mixed $value): self
    {
        return $this->with(FieldValue::localized($field, $language, $value));
    }

    /**
     * A field of an object brick; the brick is added where the object has none yet.
     *
     * @param string $bricksField the class's object bricks field
     * @param string $brick the brick's key
     * @param string|null $language the language, for a localized field inside the brick
     */
    public function withBrickValue(
        string $bricksField,
        string $brick,
        string $field,
        mixed $value,
        ?string $language = null,
    ): self {
        return $this->with(FieldValue::inBrick($bricksField, $brick, $field, $value, $language));
    }

    /**
     * A key of a classification store, by the names of its group and key; the group is made active
     * on the object.
     *
     * @param string $language `default` for the value every language falls back to
     */
    public function withClassificationValue(
        string $storeField,
        string $group,
        string $key,
        mixed $value,
        string $language = 'default',
    ): self {
        return $this->with(FieldValue::classification($storeField, $group, $key, $language, $value));
    }

    /** @return list<FieldValue> in the order they were first set */
    public function values(): array
    {
        return array_values($this->values);
    }

    private function with(FieldValue $value): self
    {
        $values = $this->values;
        $values[$value->place()] = $value;

        return new self($this->id, $this->create, $values);
    }
}
