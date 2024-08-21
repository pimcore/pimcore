<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Extension;

use Pimcore\Model;
use Pimcore\Model\DataObject\Concrete;

trait Text
{
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    public function isEmpty(mixed $data): bool
    {
        return strlen((string) $data) < 1;
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /**
     * @see Data::getVersionPreview
     */
    public function getVersionPreview(mixed $data, Model\DataObject\Concrete $object = null, array $params = []): string
    {
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }
}
