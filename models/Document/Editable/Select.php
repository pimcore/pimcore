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
class Select extends Model\Document\Editable
{
    /**
     * Contains the current selected value
     *
     * @internal
     *
     * @var string
     */
    protected $text;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        return $this->text;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        $this->text = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        $this->text = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->text);
    }
}
