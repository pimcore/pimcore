<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Getter;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element\Tag;

class TagsGetter implements IGetter {

    public static function get($element, $config = null) {

        $type = 'object';
        if($element instanceof Asset) {
            $type = 'asset';
        } elseif($element instanceof Document) {
            $type = 'document';
        }

        $tags = Tag::getTagsForElement($type, $element->getId());


        if(!$config || !$config->includeParentTags) {
            return $tags;
        }

        $result = [];
        foreach($tags as $tag) {
            $result[] = $tag;

            $parent = $tag->getParent();
            while($parent instanceof Tag) {
                $result[] = $parent;
                $parent = $parent->getParent();

            }
        }

        return $result;
    }

}
