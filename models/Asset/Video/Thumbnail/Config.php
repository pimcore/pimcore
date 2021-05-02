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

namespace Pimcore\Model\Asset\Video\Thumbnail;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Asset\Video\Thumbnail\Config\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class Config extends Model\AbstractModel
{
    use Model\Asset\Thumbnail\ClearTempFilesTrait;

    /**
     * format of array:
     * array(
     array(
     "method" => "myName",
     "arguments" =>
     array(
     "width" => 345,
     "height" => 200
     )
     )
     * )
     *
     * @internal
     *
     * @var array
     */
    protected $items = [];

    /**
     * @internal
     *
     * @var array
     */
    public $medias = [];

    /**
     * @internal
     *
     * @var string
     */
    protected $name = '';

    /**
     * @internal
     *
     * @var string
     */
    protected $description = '';

    /**
     * @internal
     *
     * @var string
     */
    protected $group = '';

    /**
     * @internal
     *
     * @var int
     */
    protected $videoBitrate;

    /**
     * @internal
     *
     * @var int
     */
    protected $audioBitrate;

    /**
     * @internal
     *
     * @var int
     */
    protected $modificationDate;

    /**
     * @internal
     *
     * @var int
     */
    protected $creationDate;

    /**
     * @internal
     *
     * @var string
     */
    public $filenameSuffix;

    /**
     * @param string $name
     *
     * @return null|Config
     *
     * @throws \Exception
     */
    public static function getByName($name)
    {
        $cacheKey = 'videothumb_' . crc32($name);

        try {
            $thumbnail = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$thumbnail) {
                throw new \Exception('Thumbnail in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $thumbnail = new self();
                $thumbnail->getDao()->getByName($name);
                \Pimcore\Cache\Runtime::set($cacheKey, $thumbnail);
            } catch (Model\Exception\NotFoundException $e) {
                return null;
            }
        }

        return $thumbnail;
    }

    /**
     * @internal
     *
     * @return Config
     */
    public static function getPreviewConfig()
    {
        $config = new self();
        $config->setName('pimcore-system-treepreview');
        $config->setAudioBitrate(128);
        $config->setVideoBitrate(700);

        $config->setItems([
            [
                'method' => 'scaleByWidth',
                'arguments' =>
                [
                    'width' => 500,
                ],
            ],
        ]);

        return $config;
    }

    /**
     * @param string $name
     */
    private function createMediaIfNotExists($name)
    {
        if (!array_key_exists($name, $this->medias)) {
            $this->medias[$name] = [];
        }
    }

    /**
     * @internal
     *
     * @param string $name
     * @param array $parameters
     * @param string $media
     *
     * @return bool
     */
    public function addItem($name, $parameters, $media = null)
    {
        $item = [
            'method' => $name,
            'arguments' => $parameters,
        ];

        // default is added to $this->items for compatibility reasons
        if (!$media || $media == 'default') {
            $this->items[] = $item;
        } else {
            $this->createMediaIfNotExists($media);
            $this->medias[$media][] = $item;
        }

        return true;
    }

    /**
     * @internal
     *
     * @param int $position
     * @param string $name
     * @param array $parameters
     *
     * @return bool
     */
    public function addItemAt($position, $name, $parameters, $media = null)
    {
        if (!$media || $media == 'default') {
            $itemContainer = &$this->items;
        } else {
            $this->createMediaIfNotExists($media);
            $itemContainer = &$this->medias[$media];
        }

        array_splice($itemContainer, $position, 0, [[
            'method' => $name,
            'arguments' => $parameters,
        ]]);

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function selectMedia($name)
    {
        if (preg_match('/^[0-9a-f]{8}$/', $name)) {
            $hash = $name;
        } else {
            $hash = hash('crc32b', $name);
        }

        foreach ($this->medias as $key => $value) {
            $currentHash = hash('crc32b', $key);
            if ($key === $name || $currentHash === $hash) {
                $this->setItems($value);
                $this->setFilenameSuffix('media--' . $currentHash . '--query');

                return true;
            }
        }

        return false;
    }

    /**
     * @internal
     */
    public function resetItems()
    {
        $this->items = [];
        $this->medias = [];
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array $medias
     */
    public function setMedias($medias)
    {
        $this->medias = $medias;
    }

    /**
     * @return array
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @return bool
     */
    public function hasMedias()
    {
        return !empty($this->medias);
    }

    /**
     * @param string $filenameSuffix
     */
    public function setFilenameSuffix($filenameSuffix)
    {
        $this->filenameSuffix = $filenameSuffix;
    }

    /**
     * @return string
     */
    public function getFilenameSuffix()
    {
        return $this->filenameSuffix;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $audioBitrate
     *
     * @return $this
     */
    public function setAudioBitrate($audioBitrate)
    {
        $this->audioBitrate = (int) $audioBitrate;

        return $this;
    }

    /**
     * @return int
     */
    public function getAudioBitrate()
    {
        return $this->audioBitrate;
    }

    /**
     * @param int $videoBitrate
     *
     * @return $this
     */
    public function setVideoBitrate($videoBitrate)
    {
        $this->videoBitrate = (int) $videoBitrate;

        return $this;
    }

    /**
     * @return int
     */
    public function getVideoBitrate()
    {
        return $this->videoBitrate;
    }

    /**
     * @internal
     *
     * @return array
     */
    public function getEstimatedDimensions()
    {
        $dimensions = [];
        $transformations = $this->getItems();
        if (is_array($transformations) && count($transformations) > 0) {
            foreach ($transformations as $transformation) {
                if (!empty($transformation)) {
                    if (is_array($transformation['arguments'])) {
                        foreach ($transformation['arguments'] as $key => $value) {
                            if ($key == 'width' || $key == 'height') {
                                $dimensions[$key] = $value;
                            }
                        }
                    }
                }
            }
        }

        return $dimensions;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup(string $group): void
    {
        $this->group = $group;
    }
}
