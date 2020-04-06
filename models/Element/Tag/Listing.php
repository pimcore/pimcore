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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Tag;

use Pimcore\Model;

/**
 * @method Model\Element\Tag\Listing\Dao getDao()
 * @method Model\Element\Tag[] load()
 * @method Model\Element\Tag current()
 * @method int[] loadIdList()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\Element\Tag[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $tags = null;

    public function __construct()
    {
        $this->tags = & $this->data;
    }

    /**
     * @param Model\Element\Tag[]|null $tags
     *
     * @return static
     */
    public function setTags($tags)
    {
        return $this->setData($tags);
    }

    /**
     * @return Model\Element\Tag[]
     */
    public function getTags()
    {
        return $this->getData();
    }
}
