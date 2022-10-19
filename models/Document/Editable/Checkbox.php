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
     * @var bool
     */
    protected bool $value = false;

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'checkbox';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        return (string)$this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource(mixed $data): EditableInterface|Checkbox|static
    {
        $this->value = (bool) $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode(mixed $data): EditableInterface|Checkbox|static
    {
        $this->value = (bool) $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return !$this->value;
    }

    /**
     * @return bool
     */
    public function isChecked(): bool
    {
        return $this->value;
    }
}
