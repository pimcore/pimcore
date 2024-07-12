<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\SelectOptions\Data;

use JsonSerializable;

class SelectOption implements JsonSerializable
{
    public const PROPERTY_VALUE = 'value';

    public const PROPERTY_LABEL = 'label';

    public const PROPERTY_NAME = 'name';

    public function __construct(
        protected string $value,
        protected string $label,
        protected string $name = '',
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return $this
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function hasValue(): bool
    {
        return $this->value !== '';
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function hasLabel(): bool
    {
        return $this->label !== '';
    }

    /**
     * @return $this
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function hasName(): bool
    {
        return $this->name !== '';
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            static::PROPERTY_VALUE => $this->getValue(),
            static::PROPERTY_LABEL => $this->getLabel(),
            static::PROPERTY_NAME => $this->getName(),
        ];
    }

    public static function createFromData(array $data): static
    {
        $value = $data[static::PROPERTY_VALUE] ?? '';
        $label = $data[static::PROPERTY_LABEL] ?? '';
        $name = $data[static::PROPERTY_NAME] ?? '';

        return new static($value, $label, $name);
    }
}
