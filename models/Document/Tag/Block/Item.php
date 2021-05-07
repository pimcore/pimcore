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

namespace Pimcore\Model\Document\Tag\Block;

use Pimcore\Model\Document\Editable\Block\Item as EditableBlockItem;

@trigger_error(sprintf('Class "%s" is deprecated since v6.8 and will be removed in Pimcore 10. Use "%s" instead.', Item::class, EditableBlockItem::class), E_USER_DEPRECATED);

class_exists(EditableBlockItem::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Model\Document\Editable\Block\Item instead.
     */
    class Item extends EditableBlockItem
    {
    }
}
