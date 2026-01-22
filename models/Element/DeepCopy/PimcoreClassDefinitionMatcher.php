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

namespace Pimcore\Model\Element\DeepCopy;

use DeepCopy\Matcher\Matcher;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
class PimcoreClassDefinitionMatcher implements Matcher
{
    private string $matchType;

    /**
     * PimcoreClassDefinitionMatcher constructor.
     *
     */
    public function __construct(string $matchType)
    {
        $this->matchType = $matchType;
    }

    /**
     * @param object $object
     * @param string $property
     *
     */
    public function matches($object, $property): bool
    {
        // TODO check if matcher only works for container type object (but not for localized fields, bricks, etc...)

        if ($object instanceof Concrete) {
            // do not call getClass on the object as this will set the class again
            $def = ClassDefinition::getById($object->getClassId());
            if ($def) {
                return $def->getFieldDefinition($property) instanceof $this->matchType;
            }
        }

        return false;
    }
}
