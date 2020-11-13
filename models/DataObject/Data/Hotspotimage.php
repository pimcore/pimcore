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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\Service;

class Hotspotimage implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;
    /**
     * @var Asset\Image|null
     */
    protected $image;

    /**
     * @var array[]
     */
    protected $hotspots;

    /**
     * @var array[]
     */
    protected $marker;

    /**
     * @var array[]
     */
    protected $crop;

    /**
     * @param Asset\Image|int|null $image
     * @param array $hotspots
     * @param array $marker
     * @param array $crop
     */
    public function __construct($image = null, $hotspots = [], $marker = [], $crop = [])
    {
        if ($image instanceof Asset\Image) {
            $this->image = $image;
        } elseif (is_numeric($image)) {
            $this->image = Asset\Image::getById($image);
        }

        if (is_array($hotspots)) {
            $this->hotspots = [];
            foreach ($hotspots as $h) {
                $this->hotspots[] = $h;
            }
        }

        if (is_array($marker)) {
            $this->marker = [];
            foreach ($marker as $m) {
                $this->marker[] = $m;
            }
        }

        if (is_array($crop)) {
            $this->crop = $crop;
        }
        $this->markMeDirty();
    }

    /**
     * @param array[] $hotspots
     *
     * @return $this
     */
    public function setHotspots($hotspots)
    {
        $this->hotspots = $hotspots;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return array|array[]
     */
    public function getHotspots()
    {
        return $this->hotspots;
    }

    /**
     * @param array[] $marker
     *
     * @return $this
     */
    public function setMarker($marker)
    {
        $this->marker = $marker;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return array|array[]
     */
    public function getMarker()
    {
        return $this->marker;
    }

    /**
     * @param array[] $crop
     */
    public function setCrop($crop)
    {
        $this->crop = $crop;
        $this->markMeDirty();
    }

    /**
     * @return array[]
     */
    public function getCrop()
    {
        return $this->crop;
    }

    /**
     * @param Asset\Image $image
     *
     * @return $this
     */
    public function setImage($image)
    {
        $this->image = $image;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return Asset\Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string|array|Asset\Image\Thumbnail\Config $thumbnailName
     * @param bool $deferred
     *
     * @return Asset\Image\Thumbnail|string
     */
    public function getThumbnail($thumbnailName = null, $deferred = true)
    {
        if (!$this->getImage()) {
            return '';
        }

        $crop = null;
        if (is_array($this->getCrop())) {
            $crop = $this->getCrop();
        }

        $thumbConfig = $this->getImage()->getThumbnailConfig($thumbnailName);
        if (!$thumbConfig && $crop) {
            $thumbConfig = new Asset\Image\Thumbnail\Config();
        }

        if ($crop) {
            if ($thumbConfig->hasMedias()) {
                $medias = $thumbConfig->getMedias() ?: [];

                foreach ($medias as $mediaName => $mediaConfig) {
                    $thumbConfig->addItemAt(0, 'cropPercent', [
                        'width' => $crop['cropWidth'],
                        'height' => $crop['cropHeight'],
                        'y' => $crop['cropTop'],
                        'x' => $crop['cropLeft'],
                    ], $mediaName);
                }
            }

            $thumbConfig->addItemAt(0, 'cropPercent', [
                'width' => $crop['cropWidth'],
                'height' => $crop['cropHeight'],
                'y' => $crop['cropTop'],
                'x' => $crop['cropLeft'],
            ]);

            $hash = md5(\Pimcore\Tool\Serialize::serialize($thumbConfig->getItems()));
            $thumbConfig->setName($thumbConfig->getName() . '_auto_' . $hash);
        }

        return $this->getImage()->getThumbnail($thumbConfig, $deferred);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->image) {
            return $this->image->__toString();
        }

        return '';
    }

    public function __wakeup()
    {
        if ($this->image instanceof ElementDescriptor) {
            $image = Service::getElementById($this->image->getType(), $this->image->getId());
            if ($image instanceof Asset\Image) {
                $this->image = $image;
            } else {
                $this->image = null;
            }
        }
    }
}
