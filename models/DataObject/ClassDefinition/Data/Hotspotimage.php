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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Hotspotimage extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, NormalizerInterface
{
    use Extension\ColumnType;
    use ImageTrait;
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'hotspotimage';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = ['image' => 'int(11)', 'hotspots' => 'text'];

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = ['image' => 'int(11)', 'hotspots' => 'text'];

    /**
     * @internal
     *
     * @var int
     */
    public $ratioX;

    /**
     * @internal
     *
     * @var int
     */
    public $ratioY;

    /**
     * @internal
     *
     * @var string
     */
    public $predefinedDataTemplates;

    /**
     * @param int $ratioX
     */
    public function setRatioX($ratioX)
    {
        $this->ratioX = $ratioX;
    }

    /**
     * @return int
     */
    public function getRatioX()
    {
        return $this->ratioX;
    }

    /**
     * @param int $ratioY
     */
    public function setRatioY($ratioY)
    {
        $this->ratioY = $ratioY;
    }

    /**
     * @return int
     */
    public function getRatioY()
    {
        return $this->ratioY;
    }

    /**
     * @return string
     */
    public function getPredefinedDataTemplates()
    {
        return $this->predefinedDataTemplates;
    }

    /**
     * @param string $predefinedDataTemplates
     */
    public function setPredefinedDataTemplates($predefinedDataTemplates)
    {
        $this->predefinedDataTemplates = $predefinedDataTemplates;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Hotspotimage) {
            $imageId = null;
            if ($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }

            $metaData = [
                'hotspots' => $data->getHotspots(),
                'marker' => $data->getMarker(),
                'crop' => $data->getCrop(),
            ];

            $metaData = Serialize::serialize($metaData);

            return [
                $this->getName() . '__image' => $imageId,
                $this->getName() . '__hotspots' => $metaData,
            ];
        }

        return [
            $this->getName() . '__image' => null,
            $this->getName() . '__hotspots' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return DataObject\Data\Hotspotimage|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $imageId = $data[$this->getName() . '__image'];
        $image = Asset::getById($imageId);
        if ($image) {
            $metaData = $data[$this->getName() . '__hotspots'];

            // check if the data is JSON (backward compatibility)
            $md = json_decode($metaData, true);
            if (!$md) {
                $md = Serialize::unserialize($metaData);
            } elseif (is_array($md)) {
                $md['hotspots'] = $md;
            }

            $hotspots = empty($md['hotspots']) ? null : $md['hotspots'];
            $marker = empty($md['marker']) ? null : $md['marker'];
            $crop = empty($md['crop']) ? null : $md['crop'];

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

            $hotspots = $rewritePath($hotspots);
            $marker = $rewritePath($marker);

            $value = new DataObject\Data\Hotspotimage($imageId, $hotspots, $marker, $crop);

            if (isset($params['owner'])) {
                $value->_setOwner($params['owner']);
                $value->_setOwnerFieldname($params['fieldname']);
                $value->_setOwnerLanguage($params['language'] ?? null);
            }

            return $value;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\Hotspotimage $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Data\Hotspotimage|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Hotspotimage) {
            $imageId = null;
            if ($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }

            $rewritePath = function ($data) {
                if (!is_array($data)) {
                    return [];
                }

                foreach ($data as &$element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as &$metaData) {
                            if ($metaData['value'] instanceof Element\ElementInterface) {
                                $metaData['value'] = $metaData['value']->getRealFullPath();
                            }
                        }
                    }
                }

                return $data;
            };

            $marker = $rewritePath($data->getMarker());
            $hotspots = $rewritePath($data->getHotspots());

            $marker = object2array($marker);
            $hotspots = object2array($hotspots);

            return [
                'id' => $imageId,
                'hotspots' => $hotspots,
                'marker' => $marker,
                'crop' => $data->getCrop(),
            ];
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Hotspotimage
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
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

        if (array_key_exists('marker', $data) && is_array($data['marker']) && count($data['marker']) > 0) {
            $data['marker'] = $rewritePath($data['marker']);
        }

        if (array_key_exists('hotspots', $data) && is_array($data['hotspots']) && count($data['hotspots']) > 0) {
            $data['hotspots'] = $rewritePath($data['hotspots']);
        }

        return new DataObject\Data\Hotspotimage($data['id'] ?? null, $data['hotspots'] ?? [], $data['marker'] ?? [], $data['crop'] ?? []);
    }

    /**
     * @param DataObject\Data\Hotspotimage $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Hotspotimage
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\Hotspotimage|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail?id=' . $data->getImage()->getId() . '&width=100&height=100&aspectratio=true" />';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Hotspotimage) {
            return base64_encode(Serialize::serialize($data));
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
    {
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            if (!array_key_exists($data->getImage()->getCacheTag(), $tags)) {
                $tags = $data->getImage()->getCacheTags($tags);
            }

            $getMetaDataCacheTags = function ($d, $tags) {
                if (!is_array($d)) {
                    return $tags;
                }

                foreach ($d as $element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as $metaData) {
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

            $marker = $data->getMarker();
            $hotspots = $data->getHotspots();

            $tags = $getMetaDataCacheTags($marker, $tags);
            $tags = $getMetaDataCacheTags($hotspots, $tags);
        }

        return $tags;
    }

    /**
     * @param DataObject\Data\Hotspotimage|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            $dependencies['asset_' . $data->getImage()->getId()] = [
                'id' => $data->getImage()->getId(),
                'type' => 'asset',
            ];

            $getMetaDataDependencies = function ($data, $dependencies) {
                if (!is_array($data)) {
                    return $dependencies;
                }

                foreach ($data as $element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as $metaData) {
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

            $dependencies = $getMetaDataDependencies($data->getMarker(), $dependencies);
            $dependencies = $getMetaDataDependencies($data->getHotspots(), $dependencies);
        }

        return $dependencies;
    }

    /**
     * @param DataObject\Data\Hotspotimage|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     *
     * @return Element\ElementInterface
     *
     * @throws \Exception
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        $this->doRewriteIds($object, $idMapping, $params, $data);

        return $data;
    }

    /**
     * @internal
     */
    public function doRewriteIds($object, $idMapping, $params, $data)
    {
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage()) {
            $id = $data->getImage()->getId();
            if (array_key_exists('asset', $idMapping) and array_key_exists($id, $idMapping['asset'])) {
                $data->setImage(Asset::getById($idMapping['asset'][$id]));

                // reset hotspot, marker & crop
                $data->setHotspots(null);
                $data->setMarker(null);
                $data->setCrop(null);
            }

            if ($data->getHotspots()) {
                $data->setHotspots($this->rewriteIdsInDataEntries($data->getHotspots(), $idMapping));
            }
            if ($data->getMarker()) {
                $data->setMarker($this->rewriteIdsInDataEntries($data->getMarker(), $idMapping));
            }
        }

        return $data;
    }

    /**
     * @param array|null $dataArray
     * @param array $idMapping
     *
     * @return array
     */
    private function rewriteIdsInDataEntries($dataArray, $idMapping)
    {
        $newDataArray = [];
        if ($dataArray) {
            foreach ($dataArray as $dataArrayEntry) {
                if ($dataArrayEntry['data']) {
                    $newData = [];
                    foreach ($dataArrayEntry['data'] as $dataEntry) {
                        //rewrite objects
                        if ($dataEntry['type'] == 'object' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if (array_key_exists('object', $idMapping) and array_key_exists($id, $idMapping['object'])) {
                                $dataEntry['value'] = DataObject::getById($idMapping['object'][$id]);
                            }
                        }
                        //rewrite assets
                        if ($dataEntry['type'] == 'asset' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if (array_key_exists('asset', $idMapping) and array_key_exists($id, $idMapping['asset'])) {
                                $dataEntry['value'] = Asset::getById($idMapping['asset'][$id]);
                            }
                        }
                        //rewrite documents
                        if ($dataEntry['type'] == 'document' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if (array_key_exists('document', $idMapping) and array_key_exists($id, $idMapping['document'])) {
                                $dataEntry['value'] = Document::getById($idMapping['document'][$id]);
                            }
                        }
                        $newData[] = $dataEntry;
                    }
                    $dataArrayEntry['data'] = $newData;
                }
                $newDataArray[] = $dataArrayEntry;
            }
        }

        return $newDataArray;
    }

    /**
     * @param DataObject\Data\Hotspotimage|null $oldValue
     * @param DataObject\Data\Hotspotimage|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\Hotspotimage
            || !$newValue instanceof DataObject\Data\Hotspotimage) {
            return false;
        }

        $fd = new Image();
        if (!$fd->isEqual($oldValue->getImage(), $newValue->getImage())) {
            return false;
        }

        $oldValue = [
            'hotspots' => $oldValue->getHotspots(),
            'marker' => $oldValue->getMarker(),
            'crop' => $oldValue->getCrop(),
        ];

        $newValue = [
            'hotspots' => $newValue->getHotspots(),
            'marker' => $newValue->getMarker(),
            'crop' => $newValue->getCrop(),
        ];

        if (!$this->isEqualArray($oldValue, $newValue)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' .DataObject\Data\Hotspotimage::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' .DataObject\Data\Hotspotimage::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Hotspotimage::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Hotspotimage::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof DataObject\Data\Hotspotimage) {
            $result = [];
            $result['hotspots'] = $value->getHotspots();
            $result['marker'] = $value->getMarker();
            $result['crop'] = $value->getCrop();

            $image = $value->getImage();
            if ($image) {
                $type = Element\Service::getType($image);
                $id = $image->getId();
                $result['image'] = [
                    'type' => $type,
                    'id' => $id,
                ];
            }

            return $result;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $image = new DataObject\Data\Hotspotimage();
            $image->setHotspots($value['hotspots']);
            $image->setMarker($value['marker']);
            $image->setCrop($value['crop']);
            if ($value['image'] ?? false) {
                $type = $value['image']['type'];
                $id = $value['image']['id'];
                $asset = Element\Service::getElementById($type, $id);
                $image->setImage($asset);
            }

            return $image;
        }
    }
}
