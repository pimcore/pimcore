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

use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Contracts\EventDispatcher\Event;

class ObjectbrickDefinitionEvent extends Event
{
    protected Definition $objectbrickDefinition;

    public function __construct(Definition $objectbrickDefinition)
    {
        $this->objectbrickDefinition = $objectbrickDefinition;
    }

    public function getObjectbrickDefinition(): Definition
    {
        return $this->objectbrickDefinition;
    }

    public function setObjectbrickDefinition(Definition $objectbrickDefinition): void
    {
        $this->objectbrickDefinition = $objectbrickDefinition;
    }
}
