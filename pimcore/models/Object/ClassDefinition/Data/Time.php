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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;

class Time extends Model\Object\ClassDefinition\Data\Input
{

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
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        parent::checkValidity($data, $omitMandatoryCheck);

        if ((is_string($data) && strlen($data) != 5 && !empty($data)) || (!empty($data) && !is_string($data))) {
            throw new Model\Element\ValidationException("Wrong time format given must be a 5 digit string (eg: 06:49) [ ".$this->getName()." ]");
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = array())
    {
        return true;
    }
}
