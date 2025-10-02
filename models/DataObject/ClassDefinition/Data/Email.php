<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Pimcore\Model;

class Email extends Model\DataObject\ClassDefinition\Data\Input
{
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && is_string($data) && strlen($data) > 0) {
            $validator = new EmailValidator();
            if (!$validator->isValid($data, new RFCValidation())) {
                throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . " ] isn't a valid email address");
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    public function getFieldType(): string
    {
        return 'email';
    }
}
