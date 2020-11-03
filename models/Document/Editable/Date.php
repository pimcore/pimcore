<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
     * @var \Carbon\Carbon|null
     */
    protected $date;

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'date';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->date;
    }

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
     * @see EditableInterface::frontend
     */
    public function frontend()
    {
        $format = null;

        if (isset($this->config['outputFormat']) && $this->config['outputFormat']) {
            $format = $this->config['outputFormat'];
        } elseif (isset($this->config['format']) && $this->config['format']) {
            $format = $this->config['format'];
        } else {
            $format = \DateTime::ISO8601;
        }

        if ($this->date instanceof \DateTimeInterface) {
            $result = $this->date->formatLocalized($format);

            return $result;
        }
    }

    /**
     * @see Model\Document\Editable::getDataForResource
     *
     * @return int|null
     */
    public function getDataForResource()
    {
        $this->checkValidity();
        if ($this->date) {
            return $this->date->getTimestamp();
        }

        return null;
    }

    /**
     * @see EditableInterface::setDataFromResource
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        if ($data) {
            $this->setDateFromTimestamp($data);
        }

        return $this;
    }

    /**
     * @see EditableInterface::setDataFromEditmode
     *
     * @param mixed $data
     *
     * @return $this
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
     * @return bool
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
    protected function setDateFromTimestamp($timestamp)
    {
        $this->date = new \Carbon\Carbon();
        $this->date->setTimestamp($timestamp);
    }
}
