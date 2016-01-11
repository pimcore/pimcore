<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;

class Time extends Model\Object\ClassDefinition\Data\Input {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "time";

    /**
     * Column length
     *
     * @var integer
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
        if(strlen($minValue)) {
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
        if(strlen($maxValue)) {
            $this->maxValue = $this->toTime($maxValue);
        } else {
            $this->maxValue = null;
        }
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)){
            throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (!$omitMandatoryCheck && strlen($data)) {

            if (!$this->toTime($data)) {
                throw new \Exception("Wrong time format given must be a 5 digit string (eg: 06:49) [ ".$this->getName()." ]");
            }

            if(strlen($this->getMinValue()) && $this->isEarlier($this->getMinValue(), $data)) {
                throw new \Exception("Value in field [ ".$this->getName()." ] is not at least " . $this->getMinValue());
            }

            if(strlen($this->getMaxValue()) && $this->isLater($this->getMaxValue(), $data)) {
                throw new \Exception("Value in field [ " . $this->getName() . " ] is bigger than " . $this->getMaxValue());
            }

        }

    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }

    /**
     * @param $data
     * @return bool
     */
    public function isEmpty($data) {
        return (strlen($data) !== 5);
    }

    /**
     * Returns a 5 digit time string of a given time
     * @param $string
     * @return null|string
     */
    public function toTime($string) {

        $time = @date("H:i", strtotime($string));
        if(!$time) {
            return null;
        }

        return $time;
    }

    /**
     * Returns a timestamp representation of a given time
     * @param      $string
     * @param null $baseTimestamp
     * @return int
     */
    protected function toTimestamp($string, $baseTimestamp=null) {
        if ($baseTimestamp === null) {
            $baseTimestamp = time();
        }

        return strtotime($string, $baseTimestamp);
    }

    /**
     * Returns whether or not a time is earlier than the subject
     * @param $string
     * @return int
     */
    public function isEarlier($subject, $comparison) {
        $baseTs = time();
        return $this->toTimestamp($subject, $baseTs) > $this->toTimestamp($comparison, $baseTs);
    }

    /**
     * Returns whether or not a time is later than the subject
     * @param $string
     * @return int
     */
    public function isLater($subject, $comparison) {
        $baseTs = time();
        return $this->toTimestamp($subject, $baseTs) < $this->toTimestamp($comparison, $baseTs);
    }

}
