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

namespace Pimcore\Model\Asset;

use Pimcore;
use Pimcore\Db\Helper;
use Pimcore\File;
use Pimcore\Messenger\AssetPreviewImageMessage;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Tool\Storage;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Folder extends Model\Asset
{
    protected string $type = 'folder';

    /**
     * @internal
     *
     * @var Asset\Listing|null
     */
    protected ?Listing $children = null;

    /**
     * set the children of the document
     *
     *
     * @return $this
     */
    public function setChildren(?Listing $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function getChildren(): Listing
    {
        if ($this->children === null) {
            if ($this->getId()) {
                $list = new Asset\Listing();
                $list->setCondition('parentId = ?', $this->getId());
                $list->setOrderKey('filename');
                $list->setOrder('asc');

                $this->children = $list;
            } else {
                $list = new Listing();
                $list->setAssets([]);
                $this->children = $list;
            }
        }

        return $this->children;
    }

    public function hasChildren(): bool
    {
        return $this->getDao()->hasChildren();
    }

    /**
     * @internal
     *
     * @return resource|null
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \League\Flysystem\FilesystemException
     */
    public function getPreviewImage(bool $force = false)
    {
        $storage = Storage::get('thumbnail');
        $cacheFilePath = sprintf(
            '%s/%s/image-thumb__%s__-folder-preview%s.jpg',
            rtrim($this->getRealPath(), '/'),
            $this->getId(),
            $this->getId(),
            '-hdpi'
        );

        $tileThumbnailConfig = Asset\Image\Thumbnail\Config::getPreviewConfig();

        $limit = 42;
        $db = \Pimcore\Db::get();
        $condition = "`path` LIKE :path AND `type` IN ('image', 'video', 'document')";
        $conditionParams = [
            'path' => Helper::escapeLike($this->getRealFullPath()) . '/%',
        ];

        if ($storage->fileExists($cacheFilePath)) {
            $lastUpdate = $db->fetchOne('SELECT MAX(modificationDate) FROM assets WHERE ' . $condition . ' ORDER BY filename ASC LIMIT ' . $limit, $conditionParams);
            if ($lastUpdate < $storage->lastModified($cacheFilePath)) {
                return $storage->readStream($cacheFilePath);
            }
        }

        $list = new Asset\Listing();
        $list->setCondition($condition, $conditionParams);
        $list->setOrderKey('id');
        $list->setOrder('asc');
        $list->setLimit($limit);

        $totalImages = $list->getCount();
        $count = 0;
        $gutter = 5;
        $squareDimension = 130;
        $offsetTop = 0;
        $colums = 3;
        $skipped = false;

        if ($totalImages) {
            $collage = imagecreatetruecolor(($squareDimension * $colums) + ($gutter * ($colums - 1)), (int) ceil(($totalImages / $colums)) * ($squareDimension + $gutter));
            $background = imagecolorallocate($collage, 12, 15, 18);
            imagefill($collage, 0, 0, $background);

            foreach ($list as $asset) {
                if ($asset instanceof Document && !$asset->getPageCount()) {
                    continue;
                }

                $offsetLeft = ($squareDimension + $gutter) * ($count % $colums);
                $tileThumb = null;
                if ($asset instanceof Image) {
                    $tileThumb = $asset->getThumbnail($tileThumbnailConfig);
                } elseif ($asset instanceof Document || $asset instanceof Video) {
                    $tileThumb = $asset->getImageThumbnail($tileThumbnailConfig);
                }

                if ($tileThumb) {
                    if (!$tileThumb->exists() && !$force) {
                        // only generate if all necessary thumbs are available
                        $skipped = true;

                        Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                            new AssetPreviewImageMessage($this->getId())
                        );

                        break;
                    }

                    $stream = $tileThumb->getStream();

                    if (null === $stream) {
                        break;
                    }

                    $width = $tileThumb->getWidth();
                    $height = $tileThumb->getHeight();
                    if (!$width || !$height) {
                        break;
                    }

                    $tile = imagecreatefromstring(stream_get_contents($stream));
                    imagecopyresampled($collage, $tile, $offsetLeft, $offsetTop, 0, 0, $squareDimension, $squareDimension, $width, $height);

                    $count++;
                    if ($count % $colums === 0) {
                        $offsetTop += ($squareDimension + $gutter);
                    }
                }
            }

            if ($count && !$skipped) {
                $localFile = File::getLocalTempFilePath('jpg');
                imagejpeg($collage, $localFile, 60);

                if (filesize($localFile) > 0) {
                    $storage->write($cacheFilePath, file_get_contents($localFile));
                }

                return $storage->readStream($cacheFilePath);
            }
        }

        return null;
    }
}
