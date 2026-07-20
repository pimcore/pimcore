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

namespace Pimcore\Event\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Contracts\EventDispatcher\Event;

class ClassDefinitionEvent extends Event
{
    protected ClassDefinition $classDefinition;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(ClassDefinition $classDefinition)
    {
        $this->classDefinition = $classDefinition;
    }

    public function getClassDefinition(): ClassDefinition
    {
        return $this->classDefinition;
    }

    public function setClassDefinition(ClassDefinition $classDefinition): void
    {
        $this->classDefinition = $classDefinition;
    }
}
