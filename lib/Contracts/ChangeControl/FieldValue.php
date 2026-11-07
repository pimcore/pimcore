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
 * One value a `DataObjectChange` sets, and where it goes. Built by the change's `with…()` methods;
 * an implementation reads it.
 */
final readonly class FieldValue
{
    /**
     * @param string $field the class field: the field itself, the localized field, the brick field or the store field
     * @param string|null $language Localized and Classification: the language, `default` for a store's plain value;
     *                              Brick: the language of a localized field inside the brick, else null
     * @param string|null $brick Brick: the brick's key
     * @param string|null $brickField Brick: the field inside the brick
     * @param string|null $group Classification: the group's name
     * @param string|null $key Classification: the key's name
     */
    private function __construct(
        public FieldKind $kind,
        public string $field,
        public mixed $value,
        public ?string $language = null,
        public ?string $brick = null,
        public ?string $brickField = null,
        public ?string $group = null,
        public ?string $key = null,
    ) {
    }

    public static function field(string $field, mixed $value): self
    {
        return new self(FieldKind::Field, self::classField($field, 'field'), $value);
    }

    public static function localized(string $field, string $language, mixed $value): self
    {
        return new self(
            FieldKind::Localized,
            self::classField($field, 'field'),
            $value,
            language: self::name($language, 'language'),
        );
    }

    public static function inBrick(
        string $bricksField,
        string $brick,
        string $brickField,
        mixed $value,
        ?string $language = null,
    ): self {
        return new self(
            FieldKind::Brick,
            self::classField($bricksField, 'brick field'),
            $value,
            language: $language === null ? null : self::name($language, 'language'),
            brick: self::name($brick, 'brick'),
            brickField: self::name($brickField, 'field'),
        );
    }

    public static function classification(
        string $storeField,
        string $group,
        string $key,
        string $language,
        mixed $value,
    ): self {
        return new self(
            FieldKind::Classification,
            self::classField($storeField, 'store field'),
            $value,
            language: self::name($language, 'language'),
            group: self::name($group, 'group'),
            key: self::name($key, 'key'),
        );
    }

    /** where the value goes: a second value for the same place replaces the first */
    public function place(): string
    {
        // serialized, not joined: a name may hold any separator, and two places must never read the same
        return serialize([
            $this->kind->value,
            $this->field,
            $this->language,
            $this->brick,
            $this->brickField,
            $this->group,
            $this->key,
        ]);
    }

    /** a field of the class: names starting with `__` are reserved for an object's own state */
    private static function classField(string $name, string $what): string
    {
        if (str_starts_with($name, '__')) {
            throw new InvalidArgumentException(
                sprintf('"%s" is no %s name: names starting with "__" are reserved.', $name, $what)
            );
        }

        return self::name($name, $what);
    }

    private static function name(string $name, string $what): string
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException(sprintf('A %s needs a name.', $what));
        }

        return $name;
    }
}
