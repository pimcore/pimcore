<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Numeric extends Model\Document\Editable
{
    /**
     * Contains the current number, or an empty string if not set
     *
     * @internal
     *
     * @var string
     */
    protected $number = '';

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'numeric';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->number;
    }

    /**
     * @see EditableInterface::getData
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        return $this->number;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        $this->number = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        $this->number = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        if (is_numeric($this->number)) {
            return false;
        }

        return empty($this->number);
    }
}
