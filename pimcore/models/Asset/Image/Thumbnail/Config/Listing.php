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
 * @package    Property
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Image\Thumbnail\Config;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Asset\Image\Thumbnail\Config\Listing\Dao getDao()
 */
class Listing extends Model\Listing\JsonListing
{

    /**
     * Contains the results of the list. They are all an instance of Property\Predefined
     *
     * @var array
     */
    public $thumbnails = [];

    /**
     * @return array
     */
    public function getThumbnails()
    {
        return $this->thumbnails;
    }

    /**
     * @param $thumbnails
     * @return $this
     */
    public function setThumbnails($thumbnails)
    {
        $this->thumbnails = $thumbnails;

        return $this;
    }
}
