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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\Exception\InvalidConfigException;

/**
 * One value of a classification store key, together with the qualifiers that place it.
 *
 * It is the argument {@see \Pimcore\Model\DataObject\Classificationstore::setKeyValues()} takes, not
 * a value a key stores: a slot holds the plain value of its key's type, and this carries the
 * language, the variant and the position that slot is addressed by. An absent qualifier means the
 * default - the default language, the default variant, position 0 - so an unqualified entry
 * addresses exactly the slot {@see \Pimcore\Model\DataObject\Classificationstore::setLocalizedKeyValue()}
 * writes.
 *
 * Unlike the value objects beside it this one is immutable and carries no owner: it is read once,
 * by the setter it is handed to, and never becomes the value of a field.
 */
final class MultiValue
{
    public function __construct(
        private readonly mixed $value,
        private readonly ?string $language = null,
        private readonly ?string $variant = null,
        private readonly ?int $position = null
    ) {
        if ($language !== null && $language === '') {
            throw new InvalidConfigException('A classification store entry cannot carry an empty language.');
        }

        if ($variant !== null && $variant === '') {
            throw new InvalidConfigException('A classification store entry cannot carry an empty variant.');
        }

        if ($position !== null && $position < 0) {
            throw new InvalidConfigException(
                'A classification store entry cannot carry the negative position ' . $position . '.'
            );
        }
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }
}
