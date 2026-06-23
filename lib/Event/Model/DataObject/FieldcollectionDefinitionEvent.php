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
