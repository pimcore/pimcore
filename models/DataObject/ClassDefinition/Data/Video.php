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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Tool\Serialize;

class Video extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'video';

    /**
     * @var string|int
     */
    public $width = 0;

    /**
     * Type for the column to query
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'text';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'text';

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\Video $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Video) {
            $data = clone $data;
            $data->_setOwner(null);
            $data->_setOwnerFieldname('');
            $data->_setOwnerLanguage(null);

            if ($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getId());
            }
            if ($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getId());
            }

            $data = object2array($data->getObjectVars());

            return Serialize::serialize($data);
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param int $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Video|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data) {
            $raw = Serialize::unserialize($data);

            if ($raw['type'] === 'asset') {
                if ($asset = Asset::getById($raw['data'])) {
                    $raw['data'] = $asset;
                }
            }

            if ($raw['poster']) {
                if ($poster = Asset::getById($raw['poster'])) {
                    $raw['poster'] = $poster;
                }
            }

            if ($raw['data']) {
                $video = new DataObject\Data\Video();
                if (isset($params['owner'])) {
                    $video->_setOwner($params['owner']);
                    $video->_setOwnerFieldname($params['fieldname']);
                    $video->_setOwnerLanguage($params['language'] ?? null);
                }
                $video->setData($raw['data']);
                $video->setType($raw['type']);
                $video->setPoster($raw['poster']);
                $video->setTitle($raw['title']);
                $video->setDescription($raw['description']);

                return $video;
            }
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\Video $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Data\Video $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            $data = clone $data;
            if ($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getFullpath());
            }
            if ($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getFullpath());
            }
            $data = object2array($data->getObjectVars());
        }

        return $data;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Video|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $video = null;

        if (isset($data['type']) && $data['type'] === 'asset') {
            if ($asset = Asset::getByPath($data['data'])) {
                $data['data'] = $asset;
            } else {
                $data['data'] = null;
            }
        }

        if (!empty($data['poster'])) {
            if ($poster = Asset::getByPath($data['poster'])) {
                $data['poster'] = $poster;
            } else {
                $data['poster'] = null;
            }
        }

        if (!empty($data['data'])) {
            $video = new DataObject\Data\Video();
            $video->setData($data['data']);
            $video->setType($data['type']);
            $video->setPoster($data['poster']);
            $video->setTitle($data['title']);
            $video->setDescription($data['description']);
        }

        return $video;
    }

    /**
     * @param int $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Video
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param DataObject\Data\Video $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        $id = null;
        if ($data) {
            if ($data->getData() instanceof Asset) {
                $id = $data->getData()->getId();
            }
        }
        $result = $this->getDataForEditmode($data, $object, $params);
        if ($id) {
            $result['id'] = $id;
        }

        return $result;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\Video|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data && $data->getType() == 'asset' && $data->getData() instanceof Asset) {
            return '<img src="/admin/asset/get-video-thumbnail?id=' . $data->getData()->getId() . '&width=100&height=100&aspectratio=true" />';
        }

        return parent::getVersionPreview($data, $object, $params);
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data) {
            $value = $data->getData();
            if ($value instanceof Asset) {
                $value = $value->getId();
            }

            return $data->getType() . '~' . $value;
        }

        return '';
    }

    /**
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed|null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $video = null;

        if ($importValue && strpos($importValue, '~')) {
            list($type, $data) = explode('~', $importValue);
            if ($type && $data) {
                $video = new DataObject\Data\Video();
                $video->setType($type);
                if ($type == 'asset') {
                    if ($asset = Asset::getById($data)) {
                        $video->setData($asset);
                    } else {
                        return null;
                    }
                } else {
                    $video->setData($data);
                }
            }
        }

        return $video;
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Video) {
            $value = $data->getTitle() . ' ' . $data->getDescription();

            return $value;
        }

        return '';
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        if ($data && $data->getData() instanceof Asset) {
            if (!array_key_exists($data->getData()->getCacheTag(), $tags)) {
                $tags = $data->getData()->getCacheTags($tags);
            }
        }

        if ($data && $data->getPoster() instanceof Asset) {
            if (!array_key_exists($data->getPoster()->getCacheTag(), $tags)) {
                $tags = $data->getPoster()->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param DataObject\Data\Video|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data && $data->getData() instanceof Asset) {
            $dependencies['asset_' . $data->getData()->getId()] = [
                'id' => $data->getData()->getId(),
                'type' => 'asset',
            ];
        }

        if ($data && $data->getPoster() instanceof Asset) {
            $dependencies['asset_' . $data->getPoster()->getId()] = [
                'id' => $data->getPoster()->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Data\Video|null $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        $versionPreview = null;

        if ($data && $data->getData() instanceof Asset) {
            $versionPreview = '/admin/asset/get-video-thumbnail?id=' . $data->getData()->getId() . '&width=100&height=100&aspectratio=true';
        }

        if ($versionPreview) {
            $value = [];
            $value['src'] = $versionPreview;
            $value['type'] = 'img';

            return $value;
        }

        return '';
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $idMapping
     * @param array $params
     *
     * @return mixed
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data && $data->getData() instanceof Asset) {
            if (array_key_exists('asset', $idMapping) and array_key_exists($data->getData()->getId(), $idMapping['asset'])) {
                $data->setData(Asset::getById($idMapping['asset'][$data->getData()->getId()]));
            }
        }

        if ($data && $data->getPoster() instanceof Asset) {
            if (array_key_exists('asset', $idMapping) and array_key_exists($data->getPoster()->getId(), $idMapping['asset'])) {
                $data->setPoster(Asset::getById($idMapping['asset'][$data->getPoster()->getId()]));
            }
        }

        return $data;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof DataObject\Data\Video) {
            $result = [];
            $result['type'] = $value->getType();
            if ($value->getTitle()) {
                $result['title'] = $value->getTitle();
            }

            if ($value->getDescription()) {
                $result['description'] = $value->getDescription();
            }

            $poster = $value->getPoster();
            if ($poster) {
                $result['poster'] = [
                    'type' => Model\Element\Service::getType($poster),
                    'id' => $poster->getId(),
                ];
            }

            $data = $value->getData();

            if ($data && $value->getType() == 'asset') {
                $result['data'] = [
                    'type' => Model\Element\Service::getType($data),
                    'id' => $data->getId(),
                ];
            } else {
                $result['data'] = $data;
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     * @param mixed $value
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $video = new DataObject\Data\Video();
            $video->setType($value['type']);
            $video->setTitle($value['title']);
            $video->setDescription($value['description']);

            if ($value['poster']) {
                $video->setPoster(Model\Element\Service::getElementById($value['poster']['type'], $value['poster']['id']));
            }

            if ($value['data']) {
                if (is_array($value['data'])) {
                    $video->setData(Model\Element\Service::getElementById($value['data']['type'], $value['data']['id']));
                } else {
                    $video->setData($value['data']);
                }
            }

            return $video;
        }
    }

    /**
     * @param DataObject\Data\Video|null $oldValue
     * @param DataObject\Data\Video|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldData = [];
        $newData = [];

        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\Video
            || !$newValue instanceof DataObject\Data\Video
            || $oldValue->getType() != $newValue->getType()) {
            return false;
        }

        $oldData['data'] = $oldValue->getData();

        if ($oldData['data'] instanceof Asset\Video) {
            $oldData['data'] = $oldData['data']->getId();
            $oldData['poster'] = $oldValue->getPoster();
            $oldData['title'] = $oldValue->getTitle();
            $oldData['description'] = $oldValue->getDescription();
        }

        $newData['data'] = $newValue->getData();

        if ($newData['data'] instanceof Asset\Video) {
            $newData['data'] = $newData['data']->getId();
            $newData['poster'] = $newValue->getPoster();
            $newData['title'] = $newValue->getTitle();
            $newData['description'] = $newValue->getDescription();
        }

        foreach ($oldData as $key => $oValue) {
            if (!isset($newData[$key]) || $oValue !== $newData[$key]) {
                return false;
            }
        }

        return true;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Video::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Video::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Video::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Video::class . '|null';
    }
}
