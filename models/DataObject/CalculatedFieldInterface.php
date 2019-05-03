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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Data\CalculatedValue;

interface CalculatedFieldInterface
{
    /**
     * @param Concrete $object
     * @param CalculatedValue $context
     *
     * @return mixed
     */
    public static function compute(Concrete $object, CalculatedValue $context);

    /**
     * @param Concrete $object
     * @param CalculatedValue $context
     *
     * @return mixed
     */
    public static function getCalculatedValueForEditMode(Concrete $object, CalculatedValue $context);
}
