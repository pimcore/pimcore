<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\XliffBundle\Event\Model;

use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Symfony\Contracts\EventDispatcher\Event;

class TranslationXliffEvent extends Event
{
    protected AttributeSet $attributeSet;

    public function __construct(AttributeSet $attributeSet)
    {
        $this->attributeSet = $attributeSet;
    }

    public function getAttributeSet(): AttributeSet
    {
        return $this->attributeSet;
    }
}
