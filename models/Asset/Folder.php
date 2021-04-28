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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\File;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Tool\Storage;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Folder extends Model\Asset
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'folder';

    /**
     * @internal
     *
     * @var Asset[]
     */
    protected $children;

    /**
     * @internal
     *
     * @var bool|null
     */
    protected $hasChildren;

    /**
     * set the children of the document
     *
     * @param Asset[] $children
     *
     * @return Folder
     */
    public function setChildren($children)
    {
        $this->children = $children;
        if (is_array($children) and count($children) > 0) {
            $this->hasChildren = true;
        } else {
            $this->hasChildren = false;
        }

        return $this;
    }

    /**
     * @return Asset[]|self[]
     */
    public function getChildren()
    {
        if ($this->children === null) {
            $list = new Asset\Listing();
            $list->setCondition('parentId = ?', $this->getId());
            $list->setOrderKey('filename');
            $list->setOrder('asc');

            $this->children = $list->getAssets();
        }

        return $this->children;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        if (is_bool($this->hasChildren)) {
            if (($this->hasChildren && empty($this->children)) || (!$this->hasChildren && !empty($this->children))) {
                return $this->getDao()->hasChildren();
            }

            return $this->hasChildren;
        }

        return $this->getDao()->hasChildren();
    }

    /**
     * @internal
     *
     * @param bool $hdpi
     *
     * @return resource|null
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \League\Flysystem\FilesystemException
     */
    public function getPreviewImage(bool $hdpi = false)
    {
        $storage = Storage::get('thumbnail');
        $cacheFilePath = sprintf('%s/image-thumb__%s__-folder-preview%s.jpg',
            rtrim($this->getRealFullPath(), '/'),
            $this->getId(),
            ($hdpi ? '-hdpi' : '')
        );

        $tileThumbnailConfig = Asset\Image\Thumbnail\Config::getPreviewConfig($hdpi);

        $limit = 42;
        $db = \Pimcore\Db::get();
        $condition = "path LIKE :path AND type IN ('image', 'video', 'document')";
        $conditionParams = [
            'path' => $db->escapeLike($this->getRealFullPath()) . '/%',
        ];

        if ($storage->fileExists($cacheFilePath)) {
            $lastUpdate = $db->fetchOne('SELECT MAX(modificationDate) FROM assets WHERE ' . $condition . ' ORDER BY filename ASC LIMIT ' . $limit, $conditionParams);
            if ($lastUpdate < $storage->lastModified($cacheFilePath)) {
                return $storage->readStream($cacheFilePath);
            }
        }

        $list = new Asset\Listing();
        $list->setCondition($condition, $conditionParams);
        $list->setOrderKey('filename');
        $list->setOrder('asc');
        $list->setLimit($limit);

        $totalImages = $list->getTotalCount();
        $count = 0;
        $gutter = 5;
        $squareDimension = 130;
        $offsetTop = 0;
        $colums = 3;

        if ($totalImages) {
            $collage = imagecreatetruecolor(($squareDimension * $colums) + ($gutter * ($colums - 1)), ceil(($totalImages / $colums)) * ($squareDimension + $gutter));
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
                    $tile = imagecreatefromstring(stream_get_contents($tileThumb->getStream()));
                    imagecopyresampled($collage, $tile, $offsetLeft, $offsetTop, 0, 0, $squareDimension, $squareDimension, $tileThumb->getWidth(), $tileThumb->getHeight());

                    $count++;
                    if ($count % $colums === 0) {
                        $offsetTop += ($squareDimension + $gutter);
                    }
                }
            }

            if ($count) {
                $localFile = File::getLocalTempFilePath('jpg');
                imagejpeg($collage, $localFile, 60);
                $storage->write($cacheFilePath, file_get_contents($localFile));
                unlink($localFile);

                return $storage->readStream($cacheFilePath);
            }
        }

        return null;
    }
}
