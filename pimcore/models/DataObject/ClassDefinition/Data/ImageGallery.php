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
    public function getWidth()
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
     * @param DataObject\Data\ImageGallery $data
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

            foreach ($data as $key => $item) {
                $itemData = $fd->getDataForResource($item, $object, $params);
                $ids[] = $itemData['__image'];
                $hotspots[] = $itemData['__hotspots'];
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
     * @param DataObject\Data\ImageGallery $data
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

        $resultItems = [];

        $fd = new Hotspotimage();

        $images = explode(',', $images);
        for ($i = 1; $i < count($images) - 1; $i++) {
            $imageId = $images[$i];
            $hotspotData = $hotspots[$i - 1];

            $itemData = [
                $fd->getName() . '__image' => $imageId,
                $fd->getName() . '__hotspots' => $hotspotData
            ];

            $itemResult = $fd->getDataFromResource($itemData, $object, $params);
            $resultItems[] = $itemResult;
        }

        return new DataObject\Data\ImageGallery($resultItems);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForQueryResource
     *
     * @param DataObject\Data\ImageGallery $data
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
     * @param DataObject\Data\ImageGallery $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $result = [];
        if ($data instanceof DataObject\Data\ImageGallery) {
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
     * @param DataObject\Data\ImageGallery $data
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
     * @param DataObject\Data\ImageGallery $data
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
        if ($data instanceof DataObject\Data\ImageGallery) {
            return count($data->getItems()) . ' items';
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
        if ($data instanceof DataObject\Data\ImageGallery) {
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
        if ($value instanceof DataObject\Data\ImageGallery) {
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

        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $tags = $fd->getCacheTags($item, $tags);
            }
        }
        $tags = array_unique($tags);

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

        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $itemDependencies = $fd->resolveDependencies($item);
                $dependencies = array_merge($dependencies, $itemDependencies);
            }
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
        $result = [];
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof DataObject\Data\ImageGallery) {
            foreach ($data as $item) {
                $dataForResource = $this->getDataForResource($item, $object, $params);

                if ($dataForResource) {
                    if ($dataForResource['image__hotspots']) {
                        $dataForResource['image__hotspots'] = unserialize($dataForResource['image__hotspots']);
                    }
                }
                $result[] = $dataForResource;
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param array $params
     * @param null $idMapper
     *
     * @return null|Asset|DataObject\Data\ImageGallery
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        $resultItems = [];
        if (is_array($value)) {
            $fd = new Hotspotimage();
            foreach ($value as $item) {
                $resultItems[] = $fd->getFromWebserviceImport($item, $object, $params, $idMapper);
            }
        }

        return new DataObject\Data\ImageGallery($resultItems);
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
        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $fd->doRewriteIds($object, $idMapping, $params, $item);
            }
        }

        return $data;
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
        if ($value) {
            return [
                    'value' =>  $value[$this->getName() . '__images'],
                    'value2' => $value[$this->getName() . '__hotspots']
                ];
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
        if ($value) {
            $result = [];
            $result[$this->getName() . '__images'] = $value['value'];
            $result[$this->getName() . '__hotspots'] = $value['hotspots'];

            return $result;
        }
    }
}
