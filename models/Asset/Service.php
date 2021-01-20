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

use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Service extends Model\Element\Service
{
    /**
     * @var array
     */
    public static $gridSystemColumns = ['preview', 'id', 'type', 'fullpath', 'filename', 'creationDate', 'modificationDate', 'size'];

    /**
     * @var Model\User|null
     */
    protected $_user;
    /**
     * @var array
     */
    protected $_copyRecursiveIds;

    /**
     * @param Model\User $user
     */
    public function __construct($user = null)
    {
        $this->_user = $user;
    }

    /**
     * @param Asset $target
     * @param Asset $source
     *
     * @return Asset|null copied asset
     *
     * @throws \Exception
     */
    public function copyRecursive($target, $source)
    {

        // avoid recursion
        if (!$this->_copyRecursiveIds) {
            $this->_copyRecursiveIds = [];
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return null;
        }

        $source->getProperties();

        /** @var Asset $new */
        $new = Element\Service::cloneMe($source);
        $new->setObjectVar('id', null);
        if ($new instanceof Asset\Folder) {
            $new->setChildren(null);
        }

        $new->setFilename(Element\Service::getSaveCopyName('asset', $new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->setStream($source->getStream());
        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChildren() as $child) {
            $this->copyRecursive($new, $child);
        }

        if ($target instanceof Asset\Folder) {
            $this->updateChildren($target, $new);
        }

        // triggers actions after the complete asset cloning
        \Pimcore::getEventDispatcher()->dispatch(AssetEvents::POST_COPY, new AssetEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]));

        return $new;
    }

    /**
     * @param  Asset $target
     * @param  Asset $source
     *
     * @return Asset copied asset
     *
     * @throws \Exception
     */
    public function copyAsChild($target, $source)
    {
        $source->getProperties();

        /** @var Asset $new */
        $new = Element\Service::cloneMe($source);
        $new->setId(null);

        if ($new instanceof Asset\Folder) {
            $new->setChildren(null);
        }
        $new->setFilename(Element\Service::getSaveCopyName('asset', $new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->setStream($source->getStream());
        $new->save();

        if ($target instanceof Asset\Folder) {
            $this->updateChildren($target, $new);
        }

        // triggers actions after the complete asset cloning
        \Pimcore::getEventDispatcher()->dispatch(AssetEvents::POST_COPY, new AssetEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]));

        return $new;
    }

    /**
     * @param Asset $target
     * @param Asset $source
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function copyContents($target, $source)
    {

        // check if the type is the same
        if (get_class($source) != get_class($target)) {
            throw new \Exception('Source and target have to be the same type');
        }

        if (!$source instanceof Asset\Folder) {
            $target->setStream($source->getStream());
            $target->setCustomSettings($source->getCustomSettings());
        }

        $target->setUserModification($this->_user ? $this->_user->getId() : 0);
        $newProps = Element\Service::cloneMe($source->getProperties());
        $target->setProperties($newProps);
        $target->save();

        return $target;
    }

    /**
     * @param Asset $asset
     * @param array|null $fields
     * @param string|null $requestedLanguage
     * @param array $params
     *
     * @return array
     */
    public static function gridAssetData($asset, $fields = null, $requestedLanguage = null, $params = [])
    {
        $data = Element\Service::gridElementData($asset);
        $loader = null;

        if ($asset instanceof Asset && !empty($fields)) {
            $data = [
                'id' => $asset->getId(),
                'id~system' => $asset->getId(),
                'type~system' => $asset->getType(),
                'fullpath~system' => $asset->getRealFullPath(),
                'filename~system' => $asset->getKey(),
                'creationDate~system' => $asset->getCreationDate(),
                'modificationDate~system' => $asset->getModificationDate(),
                'idPath~system' => Element\Service::getIdPath($asset),
            ];

            $requestedLanguage = str_replace('default', '', $requestedLanguage);

            foreach ($fields as $field) {
                $fieldDef = explode('~', $field);
                if (isset($fieldDef[1]) && $fieldDef[1] === 'system') {
                    if ($fieldDef[0] === 'preview') {
                        $data[$field] = self::getPreviewThumbnail($asset, ['treepreview' => true, 'width' => 108, 'height' => 70, 'frame' => true]);
                    } elseif ($fieldDef[0] === 'size') {
                        /** @var Asset $asset */
                        $size = @filesize($asset->getFileSystemPath());
                        $data[$field] = formatBytes($size);
                    }
                } else {
                    if (isset($fieldDef[1])) {
                        $language = ($fieldDef[1] === 'none' ? '' : $fieldDef[1]);
                        $rawMetaData = $asset->getMetadata($fieldDef[0], $language, true, true);
                    } else {
                        $rawMetaData = $asset->getMetadata($field, $requestedLanguage, true, true);
                    }

                    $metaData = $rawMetaData['data'] ?? null;

                    if ($rawMetaData) {
                        $type = $rawMetaData['type'];
                        if (!$loader) {
                            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
                        }

                        $metaData = $rawMetaData['data'] ?? null;
                        try {
                            /** @var Data $instance */
                            $instance = $loader->build($type);
                            $metaData = $instance->getDataForListfolderGrid($rawMetaData['data'] ?? null, $rawMetaData);
                        } catch (UnsupportedException $e) {
                        }
                    }

                    $data[$field] = $metaData;
                }
            }
        }

        return $data;
    }

    /**
     * @param Asset $asset
     * @param array $params
     * @param bool $onlyMethod
     *
     * @return string|null
     */
    public static function getPreviewThumbnail($asset, $params = [], $onlyMethod = false)
    {
        $thumbnailMethod = '';
        $thumbnailUrl = null;

        if ($asset instanceof Asset\Image) {
            $thumbnailMethod = 'getThumbnail';
        } elseif ($asset instanceof Asset\Video && \Pimcore\Video::isAvailable()) {
            $thumbnailMethod = 'getImageThumbnail';
        } elseif ($asset instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
            $thumbnailMethod = 'getImageThumbnail';
        }

        if ($onlyMethod) {
            return $thumbnailMethod;
        }

        if (!empty($thumbnailMethod)) {
            $thumbnailUrl = '/admin/asset/get-' . $asset->getType() . '-thumbnail?id=' . $asset->getId();
            if (count($params) > 0) {
                $thumbnailUrl .= '&' . http_build_query($params);
            }
        }

        return $thumbnailUrl;
    }

    /**
     * @static
     *
     * @param string $path
     * @param string|null $type
     *
     * @return bool
     */
    public static function pathExists($path, $type = null)
    {
        $path = Element\Service::correctPath($path);

        try {
            $asset = new Asset();

            if (self::isValidPath($path, 'asset')) {
                $asset->getDao()->getByPath($path);

                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @static
     *
     * @param Element\ElementInterface $element
     *
     * @return Element\ElementInterface
     */
    public static function loadAllFields(Element\ElementInterface $element)
    {
        $element->getProperties();

        return $element;
    }

    /**
     * Rewrites id from source to target, $rewriteConfig contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param Asset $asset
     * @param array $rewriteConfig
     *
     * @return Asset
     */
    public static function rewriteIds($asset, $rewriteConfig)
    {
        // rewriting properties
        $properties = $asset->getProperties();
        foreach ($properties as &$property) {
            $property->rewriteIds($rewriteConfig);
        }
        $asset->setProperties($properties);

        return $asset;
    }

    /**
     * @internal
     *
     * @param array $metadata
     * @param string $mode
     *
     * @return array
     */
    public static function minimizeMetadata($metadata, string $mode)
    {
        if (!is_array($metadata)) {
            return $metadata;
        }

        $result = [];
        foreach ($metadata as $item) {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            try {
                /** @var Data $instance */
                $instance = $loader->build($item['type']);

                if ($mode == 'grid') {
                    $transformedData = $instance->getDataFromListfolderGrid($item['data'] ?? null, $item);
                } else {
                    $transformedData = $instance->getDataFromEditMode($item['data'] ?? null, $item);
                }

                $item['data'] = $transformedData;
            } catch (UnsupportedException $e) {
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array $metadata
     *
     * @return array
     */
    public static function expandMetadataForEditmode($metadata)
    {
        if (!is_array($metadata)) {
            return $metadata;
        }

        $result = [];
        foreach ($metadata as $item) {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            $transformedData = $item['data'];

            try {
                /** @var Data $instance */
                $instance = $loader->build($item['type']);
                $transformedData = $instance->getDataForEditMode($item['data'], $item);
            } catch (UnsupportedException $e) {
            }

            $item['data'] = $transformedData;
            //get the config from an predefined property-set (eg. select)
            $predefined = Model\Metadata\Predefined::getByName($item['name']);
            if ($predefined && $predefined->getType() == $item['type'] && $predefined->getConfig()) {
                $item['config'] = $predefined->getConfig();
            }

            $key = $item['name'] . '~' . $item['language'];
            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * @param Model\Asset $item
     * @param int $nr
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getUniqueKey($item, $nr = 0)
    {
        $list = new Listing();
        $key = Element\Service::getValidKey($item->getKey(), 'asset');
        if (!$key) {
            throw new \Exception('No item key set.');
        }
        if ($nr) {
            if ($item->getType() == 'folder') {
                $key = $key . '_' . $nr;
            } else {
                $keypart = substr($key, 0, strrpos($key, '.'));
                $extension = str_replace($keypart, '', $key);
                $key = $keypart . '_' . $nr . $extension;
            }
        }

        $parent = $item->getParent();
        if (!$parent) {
            throw new \Exception('You have to set a parent folder to determine a unique Key');
        }

        if (!$item->getId()) {
            $list->setCondition('parentId = ? AND `filename` = ? ', [$parent->getId(), $key]);
        } else {
            $list->setCondition('parentId = ? AND `filename` = ? AND id != ? ', [$parent->getId(), $key, $item->getId()]);
        }
        $check = $list->loadIdList();
        if (!empty($check)) {
            $nr++;
            $key = self::getUniqueKey($item, $nr);
        }

        return $key;
    }
}
