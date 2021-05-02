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
class Date extends Model\Document\Editable
{
    /**
     * Contains the date
     *
     * @internal
     *
     * @var \Carbon\Carbon|null
     */
    protected $date;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->date;
    }

    /**
     * @return \Carbon\Carbon|null
     */
    public function getDate()
    {
        return $this->getData();
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return int|null
     */
    public function getDataEditmode()
    {
        if ($this->date) {
            return $this->date->getTimestamp();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        $format = null;

        if (isset($this->config['outputFormat']) && $this->config['outputFormat']) {
            $format = $this->config['outputFormat'];
        } elseif (isset($this->config['format']) && $this->config['format']) {
            $format = $this->config['format'];
        } else {
            $format = 'Y-m-d\TH:i:sO'; // ISO8601
        }

        if ($this->date instanceof \DateTimeInterface) {
            return $this->date->formatLocalized($format);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForResource()
    {
        if ($this->date) {
            return $this->date->getTimestamp();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        if ($data) {
            $this->setDateFromTimestamp($data);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        if (strlen($data) > 5) {
            $timestamp = strtotime($data);
            $this->setDateFromTimestamp($timestamp);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        if ($this->date) {
            return false;
        }

        return true;
    }

    /**
     * @param int $timestamp
     */
    private function setDateFromTimestamp($timestamp)
    {
        $this->date = new \Carbon\Carbon();
        $this->date->setTimestamp($timestamp);
    }
}
