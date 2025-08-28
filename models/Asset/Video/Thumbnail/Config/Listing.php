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

namespace Pimcore\Model\Asset\Video\Thumbnail\Config;

use Pimcore\Model;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Listing\CallableOrderListingInterface;
use Pimcore\Model\Listing\Traits\FilterListingTrait;
use Pimcore\Model\Listing\Traits\OrderListingTrait;

/**
 * @method \Pimcore\Model\Asset\Video\Thumbnail\Config\Listing\Dao getDao()
 */
class Listing extends AbstractModel implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    /**
     * @internal
     *
     * @var \Pimcore\Model\Asset\Video\Thumbnail\Config[]|null
     */
    protected ?array $thumbnails = null;

    /**
     * @return \Pimcore\Model\Asset\Video\Thumbnail\Config[]
     */
    public function getThumbnails(): array
    {
        if ($this->thumbnails === null) {
            $this->getDao()->loadList();
        }

        return $this->thumbnails;
    }

    /**
     * @param \Pimcore\Model\Asset\Video\Thumbnail\Config[]|null $thumbnails
     *
     * @return $this
     */
    public function setThumbnails(?array $thumbnails): static
    {
        $this->thumbnails = $thumbnails;

        return $this;
    }

    /**
     * Alias of getThumbnails()
     *
     * @return Model\Asset\Video\Thumbnail\Config[]
     */
    public function load(): array
    {
        return $this->getThumbnails();
    }
}
