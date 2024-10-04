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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Image extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface
{
    /**
     * ID of the referenced image
     *
     * @internal
     */
    protected ?int $id = null;

    /**
     * The ALT text of the image
     *
     * @internal
     */
    protected string $alt = '';

    /**
     * Contains the imageobject itself
     *
     * @internal
     *
     */
    protected Asset\Image|Element\ElementDescriptor|null $image = null;

    /**
     * @internal
     *
     */
    protected bool $cropPercent = false;

    /**
     * @internal
     *
     */
    protected float $cropWidth = 0.0;

    /**
     * @internal
     *
     */
    protected float $cropHeight = 0.0;

    /**
     * @internal
     *
     */
    protected float $cropTop = 0.0;

    /**
     * @internal
     *
     */
    protected float $cropLeft = 0.0;

    /**
     * @internal
     *
     */
    protected array $hotspots = [];

    /**
     * @internal
     *
     */
    protected array $marker = [];

    /**
     * The Thumbnail config of the image
     *
     * @internal
     */
    protected array|string|null $thumbnail = null;

    public function getType(): string
    {
        return 'image';
    }

    public function getData(): mixed
    {
        return [
            'id' => $this->id,
            'alt' => $this->alt,
            'cropPercent' => $this->cropPercent,
            'cropWidth' => $this->cropWidth,
            'cropHeight' => $this->cropHeight,
            'cropTop' => $this->cropTop,
            'cropLeft' => $this->cropLeft,
            'hotspots' => $this->hotspots,
            'marker' => $this->marker,
            'thumbnail' => $this->thumbnail,
        ];
    }

    public function getDataForResource(): array
    {
        return [
            'id' => $this->id,
            'alt' => $this->alt,
            'cropPercent' => $this->cropPercent,
            'cropWidth' => $this->cropWidth,
            'cropHeight' => $this->cropHeight,
            'cropTop' => $this->cropTop,
            'cropLeft' => $this->cropLeft,
            'hotspots' => $this->hotspots,
            'marker' => $this->marker,
            'thumbnail' => $this->thumbnail,
        ];
    }

    public function getDataEditmode(): ?array
    {
        $image = $this->getImage();

        if ($image instanceof Asset\Image) {
            $rewritePath = function ($data) {
                if (!is_array($data)) {
                    return [];
                }

                foreach ($data as &$element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as &$metaData) {
                            if ($metaData instanceof Element\Data\MarkerHotspotItem) {
                                $metaData = get_object_vars($metaData);
                            }

                            if (in_array($metaData['type'], ['object', 'asset', 'document'])
                            && $el = Element\Service::getElementById($metaData['type'], $metaData['value'])) {
                                $metaData['value'] = $el;
                            }

                            if ($metaData['value'] instanceof Element\ElementInterface) {
                                $metaData['value'] = $metaData['value']->getRealFullPath();
                            }
                        }
                    }
                }

                return $data;
            };

            $marker = $rewritePath($this->marker);
            $hotspots = $rewritePath($this->hotspots);

            return [
                'id' => $this->id,
                'path' => $image->getRealFullPath(),
                'alt' => $this->alt,
                'cropPercent' => $this->cropPercent,
                'cropWidth' => $this->cropWidth,
                'cropHeight' => $this->cropHeight,
                'cropTop' => $this->cropTop,
                'cropLeft' => $this->cropLeft,
                'hotspots' => $hotspots,
                'marker' => $marker,
                'thumbnail' => $this->thumbnail,
                'predefinedDataTemplates' => $this->getConfig()['predefinedDataTemplates'] ?? null,
            ];
        }

        return null;
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();
        if (isset($config['thumbnail']) && !isset($config['focal_point_context_menu_item'])) {
            $thumbConfig = Asset\Image\Thumbnail\Config::getByAutoDetect($config['thumbnail']);
            if ($thumbConfig) {
                foreach ($thumbConfig->getItems() as $item) {
                    if ($item['method'] == 'cover') {
                        $config['focal_point_context_menu_item'] = true;
                        $this->config['focal_point_context_menu_item'] = true;

                        break;
                    }
                }
            }
        }

        return $config;
    }

    public function frontend()
    {
        $image = $this->getImage();

        if ($image instanceof Asset\Image) {
            $thumbnailName = $this->config['thumbnail'] ?? null;
            if ($thumbnailName || $this->cropPercent) {
                // create a thumbnail first
                $autoName = false;

                $thumbConfig = $image->getThumbnail($thumbnailName)->getConfig();
                if (!$thumbConfig && $this->cropPercent) {
                    $thumbConfig = new Asset\Image\Thumbnail\Config();
                }

                if ($this->cropPercent) {
                    $this->applyCustomCropping($thumbConfig);
                    $autoName = true;
                }

                if (isset($this->config['highResolution']) && $this->config['highResolution'] > 1) {
                    $thumbConfig->setHighResolution($this->config['highResolution']);
                }

                // autogenerate a name for the thumbnail because it's different from the original
                if ($autoName) {
                    $thumbConfig->generateAutoName();
                }

                $deferred = true;
                if (isset($this->config['deferred'])) {
                    $deferred = $this->config['deferred'];
                }

                $thumbnail = $image->getThumbnail($thumbConfig, $deferred);
            } else {
                // we're using the thumbnail class only to generate the HTML
                $thumbnail = $image->getThumbnail();
            }

            $attributes = array_merge($this->config, [
                'alt' => $this->alt,
                'title' => $this->alt,
            ]);

            $removeAttributes = [];
            if (isset($this->config['removeAttributes']) && is_array($this->config['removeAttributes'])) {
                $removeAttributes = $this->config['removeAttributes'];
            }

            // thumbnail's HTML is always generated by the thumbnail itself
            return $thumbnail->getHtml($attributes);
        }

        return '';
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];

        $rewritePath = function ($data) {
            if (!is_array($data)) {
                return [];
            }

            foreach ($data as &$element) {
                if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                    foreach ($element['data'] as &$metaData) {
                        // this is for backward compatibility (Array vs. MarkerHotspotItem)
                        if (is_array($metaData)) {
                            $metaData = new Element\Data\MarkerHotspotItem($metaData);
                        }
                    }
                }
            }

            return $data;
        };

        $unserializedData['marker'] = $rewritePath($unserializedData['marker'] ?? []);
        $unserializedData['hotspots'] = $rewritePath($unserializedData['hotspots'] ?? []);

        $this->setData($unserializedData);

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $rewritePath = function ($data) {
            if (!is_array($data)) {
                return [];
            }

            foreach ($data as &$element) {
                if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                    foreach ($element['data'] as &$metaData) {
                        $metaData = new Element\Data\MarkerHotspotItem($metaData);
                        if (in_array($metaData['type'], ['object', 'asset', 'document'])) {
                            $el = Element\Service::getElementByPath($metaData['type'], $metaData->getValue());
                            $metaData['value'] = $el;
                        }
                    }
                }
            }

            return $data;
        };

        if (is_array($data)) {
            if (array_key_exists('marker', $data) && is_array($data['marker']) && count($data['marker']) > 0) {
                $data['marker'] = $rewritePath($data['marker']);
            }

            if (array_key_exists('hotspots', $data) && is_array($data['hotspots']) && count($data['hotspots']) > 0) {
                $data['hotspots'] = $rewritePath($data['hotspots']);
            }

            $this->setData($data);
        }

        return $this;
    }

    private function setData(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->alt = (string)($data['alt'] ?? '');
        $this->cropPercent = $data['cropPercent'] ?? false;
        $this->cropWidth = $data['cropWidth'] ?? 0;
        $this->cropHeight = $data['cropHeight'] ?? 0;
        $this->cropTop = $data['cropTop'] ?? 0;
        $this->cropLeft = $data['cropLeft'] ?? 0;
        $this->marker = $data['marker'] ?? [];
        $this->hotspots = $data['hotspots'] ?? [];
        $this->thumbnail = $data['thumbnail'] ?? null;
    }

    public function getText(): string
    {
        return $this->alt;
    }

    public function setText(string $text): void
    {
        $this->alt = $text;
    }

    public function getAlt(): string
    {
        return $this->getText();
    }

    public function getThumbnailConfig(): array|string|null
    {
        return $this->thumbnail;
    }

    public function getSrc(): string
    {
        $image = $this->getImage();
        if ($image instanceof Asset) {
            return $image->getFullPath();
        }

        return '';
    }

    public function getImage(): Asset\Image|null
    {
        if (!$this->image instanceof Asset\Image) {
            $this->image = $this->getId() ? Asset\Image::getById($this->getId()) : null;
        }

        return $this->image;
    }

    /**
     * @return $this
     */
    public function setImage(Asset\Image|ElementDescriptor|null $image): static
    {
        $this->image = $image;

        if ($image instanceof Asset\Image) {
            $this->setId($image->getId());
        }

        return $this;
    }

    /**
     *
     * @return $this
     */
    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getThumbnail(array|string|Asset\Image\Thumbnail\Config $conf, bool $deferred = true): Asset\Image\ThumbnailInterface|string
    {
        $image = $this->getImage();
        if ($image instanceof Asset\Image) {
            $thumbConfig = $image->getThumbnail($conf)->getConfig();
            if ($thumbConfig && $this->cropPercent) {
                $this->applyCustomCropping($thumbConfig);
                $thumbConfig->generateAutoName();
            }

            return $image->getThumbnail($thumbConfig, $deferred);
        }

        return '';
    }

    private function applyCustomCropping(Asset\Image\Thumbnail\Config $thumbConfig): void
    {
        $cropConfig = [
            'width' => $this->cropWidth,
            'height' => $this->cropHeight,
            'y' => $this->cropTop,
            'x' => $this->cropLeft,
        ];

        $thumbConfig->addItemAt(0, 'cropPercent', $cropConfig);

        // also crop media query specific configs
        if ($thumbConfig->hasMedias()) {
            foreach ($thumbConfig->getMedias() as $mediaName => $mediaItems) {
                $thumbConfig->addItemAt(0, 'cropPercent', $cropConfig, $mediaName);
            }
        }
    }

    public function isEmpty(): bool
    {
        $image = $this->getImage();
        if ($image instanceof Asset\Image) {
            return false;
        }

        return true;
    }

    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        $image = $this->getImage();

        if ($image instanceof Asset) {
            if (!array_key_exists($image->getCacheTag(), $tags)) {
                $tags = $image->getCacheTags($tags);
            }
        }

        $getMetaDataCacheTags = function ($data, $tags) {
            if (!is_array($data)) {
                return $tags;
            }

            foreach ($data as $element) {
                if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                    foreach ($element['data'] as $metaData) {
                        if ($metaData instanceof Element\Data\MarkerHotspotItem) {
                            $metaData = get_object_vars($metaData);
                        }

                        if ($metaData['value'] instanceof Element\ElementInterface) {
                            if (!array_key_exists($metaData['value']->getCacheTag(), $tags)) {
                                $tags = $metaData['value']->getCacheTags($tags);
                            }
                        }
                    }
                }
            }

            return $tags;
        };

        $tags = $getMetaDataCacheTags($this->marker, $tags);
        $tags = $getMetaDataCacheTags($this->hotspots, $tags);

        return $tags;
    }

    public function resolveDependencies(): array
    {
        $dependencies = [];
        $image = $this->getImage();

        if ($image instanceof Asset\Image) {
            $key = 'asset_' . $image->getId();

            $dependencies[$key] = [
                'id' => $image->getId(),
                'type' => 'asset',
            ];
        }

        $getMetaDataDependencies = function ($data, $dependencies) {
            if (!is_array($data)) {
                return $dependencies;
            }

            foreach ($data as $element) {
                if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                    foreach ($element['data'] as $metaData) {
                        if ($metaData instanceof Element\Data\MarkerHotspotItem) {
                            $metaData = get_object_vars($metaData);
                        }

                        if ($metaData['value'] instanceof Element\ElementInterface) {
                            $dependencies[$metaData['type'] . '_' . $metaData['value']->getId()] = [
                                'id' => $metaData['value']->getId(),
                                'type' => $metaData['type'],
                            ];
                        }
                    }
                }
            }

            return $dependencies;
        };

        $dependencies = $getMetaDataDependencies($this->marker, $dependencies);
        $dependencies = $getMetaDataDependencies($this->hotspots, $dependencies);

        return $dependencies;
    }

    /**
     * @return $this
     */
    public function setCropHeight(float $cropHeight): static
    {
        $this->cropHeight = $cropHeight;

        return $this;
    }

    public function getCropHeight(): float
    {
        return $this->cropHeight;
    }

    /**
     * @return $this
     */
    public function setCropLeft(float $cropLeft): static
    {
        $this->cropLeft = $cropLeft;

        return $this;
    }

    public function getCropLeft(): float
    {
        return $this->cropLeft;
    }

    /**
     * @return $this
     */
    public function setCropPercent(bool $cropPercent): static
    {
        $this->cropPercent = $cropPercent;

        return $this;
    }

    public function getCropPercent(): bool
    {
        return $this->cropPercent;
    }

    /**
     * @return $this
     */
    public function setCropTop(float $cropTop): static
    {
        $this->cropTop = $cropTop;

        return $this;
    }

    public function getCropTop(): float
    {
        return $this->cropTop;
    }

    /**
     * @return $this
     */
    public function setCropWidth(float $cropWidth): static
    {
        $this->cropWidth = $cropWidth;

        return $this;
    }

    public function getCropWidth(): float
    {
        return $this->cropWidth;
    }

    /**
     * @return $this
     */
    public function setHotspots(array $hotspots): static
    {
        $this->hotspots = $hotspots;

        return $this;
    }

    public function getHotspots(): array
    {
        return $this->hotspots;
    }

    /**
     * @return $this
     */
    public function setMarker(array $marker): static
    {
        $this->marker = $marker;

        return $this;
    }

    public function getMarker(): array
    {
        return $this->marker;
    }

    public function rewriteIds(array $idMapping): void
    {
        if (array_key_exists('asset', $idMapping) && array_key_exists($this->getId(), $idMapping['asset'])) {
            $this->setId($idMapping['asset'][$this->getId()]);

            // reset marker & hotspot information
            $this->setHotspots([]);
            $this->setMarker([]);
            $this->setCropPercent(false);
            $this->setImage(null);
        }
    }

    public function __sleep(): array
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ['image'];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * @internal
     *
     * https://github.com/pimcore/pimcore/issues/15932
     * used for non-nullable properties stored with null
     *
     * @TODO: Remove in Pimcore 12
     *
     */
    public function __unserialize(array $data): void
    {
        foreach (get_object_vars($this) as $property => $value) {
            $this->$property = $data["\0*\0".$property] ?? $value;
        }
    }
}
