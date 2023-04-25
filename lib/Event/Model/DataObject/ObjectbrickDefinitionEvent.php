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

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Contracts\EventDispatcher\Event;

class ObjectbrickDefinitionEvent extends Event
{
    protected Definition $classDefinition;

    /**
     * @param Definition $classDefinition
     */
    public function __construct(Definition $classDefinition)
    {
        $this->classDefinition = $classDefinition;
    }

    /**
     * @return Definition
     */
    public function getClassDefinition(): Definition
    {
        return $this->classDefinition;
    }

    /**
     * @param Definition $classDefinition
     */
    public function setClassDefinition(Definition $classDefinition): void
    {
        $this->classDefinition = $classDefinition;
    }


}
