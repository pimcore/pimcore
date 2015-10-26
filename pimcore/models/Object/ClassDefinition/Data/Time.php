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
            $this->minValue = date("H:i", strtotime($minValue));
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
            $this->maxValue = date("H:i", strtotime($maxValue));
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

        parent::checkValidity($data, $omitMandatoryCheck);

        if((is_string($data) && strlen($data) != 5 && !empty($data)) || (!empty($data) && !is_string($data))) {
            throw new \Exception("Wrong time format given must be a 5 digit string (eg: 06:49) [ ".$this->getName()." ]");
        }
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }
}
