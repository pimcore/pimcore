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
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Model;
use Pimcore\Model\Asset;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Folder extends Model\Asset
{
    /**
     * @var string
     */
    protected $type = 'folder';

    /**
     * Contains the child elements
     *
     * @var Asset[]
     */
    protected $children;

    /**
     * Indicator if there are children
     *
     * @var bool
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
            if (($this->hasChildren and empty($this->children)) or (!$this->hasChildren and !empty($this->children))) {
                return $this->getDao()->hasChildren();
            } else {
                return $this->hasChildren;
            }
        }

        return $this->getDao()->hasChildren();
    }

    /**
     * @internal
     */
    public function getPreviewImage(bool $hdpi = false): ?string
    {
        $filesystemPath = PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails' . $this->getRealFullPath() . '/folder-preview' . ($hdpi ? '-hdpi' : '') . '.jpg';
        $tileThumbnailConfig = Asset\Image\Thumbnail\Config::getPreviewConfig($hdpi);

        $limit = 42;
        $db = \Pimcore\Db::get();
        $condition = "path LIKE :path AND type IN ('image', 'video', 'document')";
        $conditionParams = [
            'path' => $db->escapeLike($this->getRealFullPath()) . '/%',
        ];

        if(file_exists($filesystemPath)) {
            $lastUpdate = $db->fetchOne('SELECT MAX(modificationDate) FROM assets WHERE ' . $condition . ' ORDER BY filename ASC LIMIT ' . $limit, $conditionParams);
            if($lastUpdate < filemtime($filesystemPath)) {
                return $filesystemPath;
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

        if($totalImages) {
            $collage = imagecreatetruecolor(($squareDimension * $colums) + ($gutter * ($colums-1)), ceil(($totalImages / $colums)) * ($squareDimension + $gutter));
            $background = imagecolorallocate($collage, 12, 15, 18);
            imagefill($collage, 0, 0, $background);

            foreach ($list as $asset) {
                $offsetLeft = ($squareDimension + $gutter) * ($count % $colums);
                $tileThumb = null;
                if ($asset instanceof Image) {
                    $tileThumb = $asset->getThumbnail($tileThumbnailConfig);
                } elseif ($asset instanceof Document || $asset instanceof Video) {
                    $tileThumb = $asset->getImageThumbnail($tileThumbnailConfig);
                }

                if ($tileThumb && preg_match('/\.jpg$/', $tileThumb->getFileSystemPath())) {
                    $tile = imagecreatefromjpeg($tileThumb->getFileSystemPath());
                    imagecopyresampled($collage, $tile, $offsetLeft, $offsetTop, 0, 0, $squareDimension, $squareDimension, $tileThumb->getWidth(), $tileThumb->getHeight());

                    $count++;
                    if($count % $colums === 0) {
                        $offsetTop += ($squareDimension + $gutter);
                    }
                }
            }

            imagejpeg($collage, $filesystemPath, 60);

            return $filesystemPath;
        }

        return null;
    }
}
