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

namespace Pimcore\Model\Asset\Image\Thumbnail\Config;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Asset\Image\Thumbnail\Config\Listing\Dao getDao()
 */
class Listing extends Model\Listing\JsonListing
{
    /**
     * @internal
     *
     * @var Model\Asset\Image\Thumbnail\Config[]|null
     */
    protected $thumbnails = null;

    /**
     * @return Model\Asset\Image\Thumbnail\Config[]
     */
    public function getThumbnails()
    {
        if ($this->thumbnails === null) {
            $this->getDao()->loadList();
        }

        return $this->thumbnails;
    }

    /**
     * @param Model\Asset\Image\Thumbnail\Config[]|null $thumbnails
     *
     * @return $this
     */
    public function setThumbnails($thumbnails)
    {
        $this->thumbnails = $thumbnails;

        return $this;
    }

    /**
     * Alias of getThumbnails()
     * @return Model\Asset\Image\Thumbnail\Config[]|null
     */
    public function load()
    {
        return $this->getThumbnails();
    }
}
