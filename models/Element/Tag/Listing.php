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

namespace Pimcore\Model\Element\Tag;

use Pimcore\Model;

/**
 * @method Model\Element\Tag\Listing\Dao getDao()
 * @method Model\Element\Tag[] load()
 * @method Model\Element\Tag|false current()
 * @method int[] loadIdList()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Model\Element\Tag[]|null $tags
     *
     * @return $this
     */
    public function setTags(?array $tags): static
    {
        return $this->setData($tags);
    }

    /**
     * @return Model\Element\Tag[]
     */
    public function getTags(): array
    {
        return $this->getData();
    }
}
