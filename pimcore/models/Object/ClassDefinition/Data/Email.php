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

class Email extends Model\Object\ClassDefinition\Data\Input
{

    /**
     * @var string
     */
    public $fieldtype = "email";


    public function checkValidity($data, $omitMandatoryCheck = false)
    {

        if (!$omitMandatoryCheck && strlen($data) > 0) {
            $hostnameValidator = new \Zend_Validate_Hostname([
                "idn" => false,
                "tld" => false
            ]);
            $validator = new \Zend_Validate_EmailAddress([
                "mx" => false,
                "deep" => false,
                "hostname" => $hostnameValidator
            ]);
            if(!$validator->isValid($data)) {
                throw new Model\Element\ValidationException("Value in field [ " . $this->getName() . " ] isn't a valid email address");
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }
}
