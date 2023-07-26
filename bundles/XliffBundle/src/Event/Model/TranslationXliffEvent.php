<?php

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
