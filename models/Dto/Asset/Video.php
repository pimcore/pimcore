<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetMetaData;
use Pimcore\Model\Dto\Asset;

class Video extends Asset
{
    public function __construct(
        private ?string $imageThumbnail,
        private ?float $duration,
        private ?int $width,
        private ?int $height,
        int $id,
        int $parentId,
        string $type,
        string $key,
        string $path,
        string $fullPath,
        ?string $mimeType,
        ?int $fileSize,
        int $userOwner,
        ?int $userModification,
        ?string $locked,
        bool $isLocked,
        /** @var AssetMetaData[] */
        array $metaData,
        ?int $creationDate,
        ?int $modificationDate,
    )
    {
        parent::__construct(
            $id,
            $parentId,
            $type,
            $key,
            $path,
            $fullPath,
            $mimeType,
            $fileSize,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $metaData,
            $creationDate,
            $modificationDate)
        ;
    }

    public function getImageThumbnail(): ?string
    {
        return $this->imageThumbnail;
    }

    public function setImageThumbnail(?string $imageThumbnail): Video
    {
        $this->imageThumbnail = $imageThumbnail;

        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(?float $duration): Video
    {
        $this->duration = $duration;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): Video
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): Video
    {
        $this->height = $height;

        return $this;
    }
}
