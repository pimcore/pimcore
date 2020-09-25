<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable\Areablock;

use Pimcore\Model\Document\Editable\Block\AbstractBlockItem;

class Item extends AbstractBlockItem
{
    protected function getItemType(): string
    {
        return 'areablock';
    }
}

class_alias(Item::class, 'Pimcore\Model\Document\Tag\Areablock\Item');
