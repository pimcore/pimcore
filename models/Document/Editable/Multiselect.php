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
class Multiselect extends Model\Document\Editable
{
    /**
     * Contains the current selected values
     *
     * @internal
     *
     * @var array
     */
    protected $values = [];

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'multiselect';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->values;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        return implode(',', $this->values);
    }

    /**
     * @return array
     */
    public function getDataEditmode()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        $this->values = \Pimcore\Tool\Serialize::unserialize($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        if (empty($data)) {
            $this->values = [];
        } elseif (is_string($data)) {
            $this->values = explode(',', $data);
        } elseif (is_array($data)) {
            $this->values = $data;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->values);
    }
}
