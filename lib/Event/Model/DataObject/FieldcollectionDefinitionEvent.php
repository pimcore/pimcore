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

use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Symfony\Contracts\EventDispatcher\Event;

class FieldcollectionDefinitionEvent extends Event
{
    protected Definition $fieldcollectionDefinition;

    public function __construct(Definition $fieldcollectionDefinition)
    {
        $this->fieldcollectionDefinition = $fieldcollectionDefinition;
    }

    public function getFieldcollectionDefinition(): Definition
    {
        return $this->fieldcollectionDefinition;
    }

    public function setFieldcollectionDefinition(Definition $fieldcollectionDefinition): void
    {
        $this->fieldcollectionDefinition = $fieldcollectionDefinition;
    }
}
