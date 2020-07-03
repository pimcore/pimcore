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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\EventDispatcher\Event;

class ClassDefinitionEvent extends Event
{
    /**
     * @var ClassDefinition
     */
    protected $classDefinition;

    /**
     * DocumentEvent constructor.
     *
     * @param ClassDefinition $classDefinition
     */
    public function __construct(ClassDefinition $classDefinition)
    {
        $this->classDefinition = $classDefinition;
    }

    /**
     * @return ClassDefinition
     */
    public function getClassDefinition()
    {
        return $this->classDefinition;
    }

    /**
     * @param ClassDefinition $classDefinition
     */
    public function setClassDefinition($classDefinition)
    {
        $this->classDefinition = $classDefinition;
    }
}
