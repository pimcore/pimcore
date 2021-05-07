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

namespace Pimcore\Model\Tool\Tag\Config;

use Pimcore\Model;

/**
 * @deprecated
 *
 * @method \Pimcore\Model\Tool\Tag\Config\Listing\Dao getDao()
 * @method  \Pimcore\Model\Tool\Tag\Config[] load()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\JsonListing
{
    /**
     * @var Model\Tool\Tag\Config[]|null
     */
    protected $tags = null;

    /**
     * @return Model\Tool\Tag\Config[]
     */
    public function getTags()
    {
        if ($this->tags === null) {
            $this->getDao()->load();
        }

        return $this->tags;
    }

    /**
     * @param Model\Tool\Tag\Config[] $tags
     *
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
