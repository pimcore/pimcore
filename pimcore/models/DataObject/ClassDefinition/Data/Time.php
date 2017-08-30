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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;

class Time extends Model\DataObject\ClassDefinition\Data\Input
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'time';

    /**
     * Column length
     *
     * @var int
     */
    public $columnLength = 5;

    /**
     * @var string
     */
    public $minValue;

    /**
     * @var string
     */
    public $maxValue;

    /**
     * @return string
     */
    public function getMinValue()
    {
        return $this->minValue;
    }

    /**
     * @param string $minValue
     */
    public function setMinValue($minValue)
    {
        if (strlen($minValue)) {
            $this->minValue = $this->toTime($minValue);
        } else {
            $this->minValue = null;
        }
    }

    /**
     * @return string
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @param string $maxValue
     */
    public function setMaxValue($maxValue)
    {
        if (strlen($maxValue)) {
            $this->maxValue = $this->toTime($maxValue);
        } else {
            $this->maxValue = null;
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        parent::checkValidity($data, $omitMandatoryCheck);

        if ((is_string($data) && strlen($data) != 5 && !empty($data)) || (!empty($data) && !is_string($data))) {
            throw new Model\Element\ValidationException('Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]');
        }

        if (!$omitMandatoryCheck && strlen($data)) {
            if (!$this->toTime($data)) {
                throw new \Exception('Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]');
            }

            if (strlen($this->getMinValue()) && $this->isEarlier($this->getMinValue(), $data)) {
                throw new \Exception('Value in field [ '.$this->getName().' ] is not at least ' . $this->getMinValue());
            }

            if (strlen($this->getMaxValue()) && $this->isLater($this->getMaxValue(), $data)) {
                throw new \Exception('Value in field [ ' . $this->getName() . ' ] is bigger than ' . $this->getMaxValue());
            }
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return strlen($data) !== 5;
    }

    /**
     * Returns a 5 digit time string of a given time
     *
     * @param $string
     *
     * @return null|string
     */
    public function toTime($string)
    {
        $time = @date('H:i', strtotime($string));
        if (!$time) {
            return null;
        }

        return $time;
    }

    /**
     * Returns a timestamp representation of a given time
     *
     * @param      $string
     * @param null $baseTimestamp
     *
     * @return int
     */
    protected function toTimestamp($string, $baseTimestamp=null)
    {
        if ($baseTimestamp === null) {
            $baseTimestamp = time();
        }

        return strtotime($string, $baseTimestamp);
    }

    /**
     * Returns whether or not a time is earlier than the subject
     *
     * @param $subject
     * @param $comparison
     *
     * @return int
     */
    public function isEarlier($subject, $comparison)
    {
        $baseTs = time();

        return $this->toTimestamp($subject, $baseTs) > $this->toTimestamp($comparison, $baseTs);
    }

    /**
     * Returns whether or not a time is later than the subject
     *
     * @param $subject
     * @param $comparison
     *
     * @return int
     */
    public function isLater($subject, $comparison)
    {
        $baseTs = time();

        return $this->toTimestamp($subject, $baseTs) < $this->toTimestamp($comparison, $baseTs);
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }
}
