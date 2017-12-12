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

class ImageGallery extends Model\DataObject\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'imageGallery';

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = ['images' => 'text', 'hotspots' => 'text'];

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = ['images' => 'text', 'hotspots' => 'text'];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\ImageGallery';

    /**
     * @var int
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var int
     */
    public $height;

    /**
     * @var string
     */
    public $uploadPath;

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
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string
     */
    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    /**
     * @param string $uploadPath
     */
    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = $uploadPath;
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
        if ($data instanceof DataObject\Data\ImageGallery) {
            $ids = [];
            $fd = new Hotspotimage();

            foreach ($data as $key =>  $item) {
                $itemData = $fd->getDataForResource($item, $object, $params);
                $ids[]= $itemData['__image'];
                $hotspots[]= $itemData['__hotspots'];
            }

            $ids = implode(',', $ids);
            if (count($ids) > 0) {
                $ids = ',' . $ids . ',';
            }

            return [
                $this->getName() . '__images' => $ids,
                $this->getName() . '__hotspots' => Serialize::serialize($hotspots)
            ];
        }

        return [
            $this->getName() . '__images' => null,
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

        if (!is_array($data)) {
            return new DataObject\Data\ImageGallery(null);
        }

        $images = $data[$this->getName() . '__images'];
        $hotspots = $data[$this->getName() . '__hotspots'];
        $hotspots = Serialize::unserialize($hotspots);

        if (!$images) {
            return new DataObject\Data\ImageGallery(null);
        }

        $resultItems = array();


        $fd = new Hotspotimage();

        $images = explode(',' , $images);
        for ($i = 1; $i < count($images) - 1; $i++) {
            $imageId = $images[$i];
            $hotspotData = $hotspots[$i - 1];

            $itemData = array(
                $fd->getName() . '__image' => $imageId,
                $fd->getName() . '__hotspots' => $hotspotData
            );


            $itemResult = $fd->getDataFromResource($itemData, $object, $params);
            $resultItems[] = $itemResult;

        }

        return new DataObject\Data\ImageGallery($resultItems);
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
        $result = array();
        if ($data instanceof  DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $itemData = $fd->getDataForEditmode($item);
                $result[] = $itemData;
            }
        }
        return $result;
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
        $resultItems = [];

        if (is_array($data)) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $resultItem = $fd->getDataFromEditmode($item);
                $resultItems[] = $resultItem;
            }
        }

        $result = new DataObject\Data\ImageGallery($resultItems);

        return $result;
    }

    /**

     * @param DataObject\Data\Hotspotimage $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO
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
        //TODO mhm ... do we really wanna support this ???
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
        //TODO same as above
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
