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
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Tool\Serialize;

class Hotspotimage extends Model\DataObject\ClassDefinition\Data\Image
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'hotspotimage';

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = ['image' => 'int(11)', 'hotspots' => 'text'];

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = ['image' => 'int(11)', 'hotspots' => 'text'];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\Hotspotimage';

    /**
     * @var int
     */
    public $ratioX;

    /**
     * @var int
     */
    public $ratioY;

    /**
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
     * @see DataObject\ClassDefinition\Data::getDataForResource
     *
     * @param DataObject\Data\Hotspotimage $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return int|null
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
                'crop' => $data->getCrop()
            ];

            $metaData = Serialize::serialize($metaData);

            return [
                $this->getName() . '__image' => $imageId,
                $this->getName() . '__hotspots' => $metaData
            ];
        }

        return [
            $this->getName() . '__image' => null,
            $this->getName() . '__hotspots' => null
        ];
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataFromResource
     *
     * @param DataObject\Data\Hotspotimage $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return Asset
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
            } else {
                if (is_array($md) && count($md)) {
                    $md['hotspots'] = $md;
                }
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

            return $value;
        }

        return null;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForQueryResource
     *
     * @param DataObject\Data\Hotspotimage $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForEditmode
     *
     * @param DataObject\Data\Hotspotimage $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return int
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
                'crop' => $data->getCrop()
            ];
        }

        return null;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromEditmode
     *
     * @param DataObject\Data\Hotspotimage $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return Asset
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

        return new DataObject\Data\Hotspotimage($data['id'], $data['hotspots'], $data['marker'], $data['crop']);
    }

    /**

     * @param DataObject\Data\Hotspotimage $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getVersionPreview
     *
     * @param Asset\Image $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail?id=' . $data->getImage()->getId() . '&width=100&height=100&aspectratio=true" />';
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Hotspotimage) {
            return base64_encode(Serialize::serialize($data));
        } else {
            return null;
        }
    }

    /**
     * @param string $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed|null|DataObject\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = null;
        $value = Serialize::unserialize(base64_decode($importValue));
        if ($value instanceof DataObject\Data\Hotspotimage) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
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
     * @param mixed $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            $dependencies['asset_' . $data->getImage()->getId()] = [
                'id' => $data->getImage()->getId(),
                'type' => 'asset'
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
                                    'type' => $metaData['type']
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
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        $dataForResource = $this->getDataForResource($data, $object, $params);

        if ($dataForResource) {
            if ($dataForResource['image__hotspots']) {
                $dataForResource['image__hotspots'] = unserialize($dataForResource['image__hotspots']);
            }

            return $dataForResource;
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param array $params
     * @param null $idMapper
     *
     * @return null|Asset|DataObject\Data\Hotspotimage
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (!is_null($value)) {
            $value = json_decode(json_encode($value), true);

            if ($value['image__image']) {
                $value['image__image'] = $idMapper->getMappedId('asset', $value['image__image']);
            }
        }

        if (is_array($value) && isset($value['image__hotspots']) && $value['image__hotspots']) {
            $value['image__hotspots'] = serialize($value['image__hotspots']);
        }
        $hotspotImage = $this->getDataFromResource($value);

        /** @var $hotspotImage DataObject\Data\Hotspotimage */
        if (!$hotspotImage) {
            return null;
        }

        $theImage = $hotspotImage->getImage();

        if (!$theImage) {
            return null;
        }

        $id = $theImage->getId();

        $asset = Asset::getById($id);
        if (empty($id)) {
            return null;
        } elseif (is_numeric($id) and $asset instanceof Asset) {
            $hotspotImage->setImage($asset);

            return $hotspotImage;
        } else {
            if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                throw new \Exception('cannot get values from web service import - invalid data, referencing unknown (hotspot) asset with id [ '.$id.' ]');
            } else {
                $idMapper->recordMappingFailure('object', $object->getId(), 'asset', $value);
            }
        }
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return null
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
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
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
     * @param $dataArray
     * @param $idMapping
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

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
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
                    'id' => $id
                ];
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $image = new DataObject\Data\Hotspotimage();
            $image->setHotspots($value['hotspots']);
            $image->setMarker($value['marker']);
            $image->setCrop($value['crop']);
            if ($value['image']) {
                $type = $value['image']['type'];
                $id = $value['image']['id'];
                $asset = Element\Service::getElementById($type, $id);
                $image->setImage($asset);
            }

            return $image;
        }
    }
}
