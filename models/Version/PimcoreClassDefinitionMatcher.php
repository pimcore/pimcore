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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Version;

use DeepCopy\Matcher\Matcher;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;

class PimcoreClassDefinitionMatcher implements Matcher
{
    public function matches($object, $property)
    {
        return $object instanceof Concrete &&
            $object->getClass()->getFieldDefinition($property) instanceof ClassDefinition\Data\CustomVersionMarshalInterface;
    }
}
