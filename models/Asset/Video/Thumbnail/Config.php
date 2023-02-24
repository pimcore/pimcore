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

namespace Pimcore\Model\Asset\Video\Thumbnail;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model;

/**
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method void delete()
 * @method void save()
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
    protected array $items = [];

    /**
     * @internal
     *
     * @var array
     */
    public array $medias = [];

    /**
     * @internal
     *
     * @var string
     */
    protected string $name = '';

    /**
     * @internal
     *
     * @var string
     */
    protected string $description = '';

    /**
     * @internal
     *
     * @var string
     */
    protected string $group = '';

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $videoBitrate = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $audioBitrate = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $modificationDate = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $creationDate = null;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $filenameSuffix = null;

    /**
     * @param string $name
     *
     * @return null|Config
     *
     * @throws \Exception
     */
    public static function getByName(string $name): ?Config
    {
        $cacheKey = 'videothumb_' . crc32($name);

        try {
            $thumbnail = RuntimeCache::get($cacheKey);
            if (!$thumbnail) {
                throw new \Exception('Thumbnail in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $thumbnail = new self();
                /** @var Model\Asset\Video\Thumbnail\Config\Dao $dao */
                $dao = $thumbnail->getDao();
                $dao->getByName($name);
                RuntimeCache::set($cacheKey, $thumbnail);
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
    public static function getPreviewConfig(): Config
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

    private function createMediaIfNotExists(string $name): void
    {
        if (!array_key_exists($name, $this->medias)) {
            $this->medias[$name] = [];
        }
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param string|null $media
     *
     * @return bool
     *
     * @internal
     */
    public function addItem(string $name, array $parameters, string $media = null): bool
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
     *
     * @internal
     */
    public function addItemAt(int $position, string $name, array $parameters, ?string $media = null): bool
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

    public function selectMedia(string $name): bool
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
    public function resetItems(): void
    {
        $this->items = [];
        $this->medias = [];
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setMedias(array $medias): void
    {
        $this->medias = $medias;
    }

    public function getMedias(): array
    {
        return $this->medias;
    }

    public function hasMedias(): bool
    {
        return !empty($this->medias);
    }

    public function setFilenameSuffix(string $filenameSuffix): void
    {
        $this->filenameSuffix = $filenameSuffix;
    }

    public function getFilenameSuffix(): ?string
    {
        return $this->filenameSuffix;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAudioBitrate(?int $audioBitrate): static
    {
        $this->audioBitrate = $audioBitrate;

        return $this;
    }

    public function getAudioBitrate(): ?int
    {
        return $this->audioBitrate;
    }

    public function setVideoBitrate(?int $videoBitrate): static
    {
        $this->videoBitrate = $videoBitrate;

        return $this;
    }

    public function getVideoBitrate(): ?int
    {
        return $this->videoBitrate;
    }

    /**
     * @internal
     *
     * @return array
     */
    public function getEstimatedDimensions(): array
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

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function __clone(): void
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
