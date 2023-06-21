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
