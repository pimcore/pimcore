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

namespace Pimcore\Event\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinitionInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ClassDefinitionEvent extends Event
{
    protected ClassDefinitionInterface $classDefinition;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(ClassDefinitionInterface $classDefinition)
    {
        $this->classDefinition = $classDefinition;
    }

    public function getClassDefinition(): ClassDefinitionInterface
    {
        return $this->classDefinition;
    }

    public function setClassDefinition(ClassDefinitionInterface $classDefinition): void
    {
        $this->classDefinition = $classDefinition;
    }
}
