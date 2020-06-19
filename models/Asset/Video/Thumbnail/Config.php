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

namespace Pimcore\Model\Asset\Video\Thumbnail;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Asset\Video\Thumbnail\Config\Dao getDao()
 * @method void save()
 * @method void delete()
 */
class Config extends Model\AbstractModel
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
     * @var array
     */
    public $items = [];

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $group = '';

    /**
     * @var int
     */
    public $videoBitrate;

    /**
     * @var int
     */
    public $audioBitrate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @param string $name
     *
     * @return null|Config
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
            } catch (\Exception $e) {
                return null;
            }
        }

        return $thumbnail;
    }

    /**
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
     * @param array $parameters
     *
     * @return bool
     */
    public function addItem($name, $parameters)
    {
        $this->items[] = [
            'method' => $name,
            'arguments' => $parameters,
        ];

        return true;
    }

    /**
     * @param int $position
     * @param string $name
     * @param array $parameters
     *
     * @return bool
     */
    public function addItemAt($position, $name, $parameters)
    {
        array_splice($this->items, $position, 0, [[
            'method' => $name,
            'arguments' => $parameters,
        ]]);

        return true;
    }

    public function resetItems()
    {
        $this->items = [];
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

    public function clearTempFiles()
    {
        $this->doClearTempFiles(PIMCORE_TEMPORARY_DIRECTORY . '/video-thumbnails', $this->getName());
    }
}
