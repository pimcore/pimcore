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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Checkbox extends Model\Document\Editable
{
    /**
     * Contains the checkbox value
     *
     * @internal
     *
     */
    protected bool $value = false;

    public function getType(): string
    {
        return 'checkbox';
    }

    public function getData(): mixed
    {
        return $this->value;
    }

    public function getValue(): mixed
    {
        return $this->getData();
    }

    public function frontend()
    {
        return (string)$this->value;
    }

    public function setDataFromResource(mixed $data): static
    {
        $this->value = (bool) $data;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->value = (bool) $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return !$this->value;
    }

    public function isChecked(): bool
    {
        return $this->value;
    }
}
