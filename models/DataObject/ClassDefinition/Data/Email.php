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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Pimcore\Model;

class Email extends Model\DataObject\ClassDefinition\Data\Input
{
    /**
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'email';

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck && strlen($data) > 0) {
            $validator = new EmailValidator();
            if (!$validator->isValid($data, new RFCValidation())) {
                throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . " ] isn't a valid email address");
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }
}
