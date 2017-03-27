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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Getter;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element\Tag;

class TagsGetter implements IGetter
{
    public static function get($element, $config = null)
    {
        $type = 'object';
        if ($element instanceof Asset) {
            $type = 'asset';
        } elseif ($element instanceof Document) {
            $type = 'document';
        }

        $tags = Tag::getTagsForElement($type, $element->getId());


        if (!$config || !$config->includeParentTags) {
            return $tags;
        }

        $result = [];
        foreach ($tags as $tag) {
            $result[] = $tag;

            $parent = $tag->getParent();
            while ($parent instanceof Tag) {
                $result[] = $parent;
                $parent = $parent->getParent();
            }
        }

        return $result;
    }
}
