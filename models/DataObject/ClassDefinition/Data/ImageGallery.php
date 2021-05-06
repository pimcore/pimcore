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

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class ImageGallery extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'imageGallery';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = ['images' => 'text', 'hotspots' => 'longtext'];

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = ['images' => 'text', 'hotspots' => 'longtext'];

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * @internal
     *
     * @var string
     */
    public $uploadPath;

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
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;
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
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
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
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\ImageGallery|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\ImageGallery) {
            $hotspots = [];
            $ids = [];
            $fd = new Hotspotimage();

            foreach ($data as $key => $item) {
                $itemData = $fd->getDataForResource($item, $object, $params);
                $ids[] = $itemData['__image'];
                $hotspots[] = $itemData['__hotspots'];
            }

            $elementCount = count($ids);
            $ids = implode(',', $ids);
            if ($elementCount > 0) {
                $ids = ',' . $ids . ',';
            }

            return [
                $this->getName() . '__images' => $ids,
                $this->getName() . '__hotspots' => Serialize::serialize($hotspots),
            ];
        }

        return [
            $this->getName() . '__images' => null,
            $this->getName() . '__hotspots' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\ImageGallery
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (!is_array($data)) {
            return $this->createEmptyImageGallery($params);
        }

        $images = $data[$this->getName() . '__images'];
        $hotspots = $data[$this->getName() . '__hotspots'];
        $hotspots = Serialize::unserialize($hotspots);

        if (!$images) {
            return $this->createEmptyImageGallery($params);
        }

        $resultItems = [];

        $fd = new Hotspotimage();

        $images = explode(',', $images);
        for ($i = 1; $i < count($images) - 1; $i++) {
            $imageId = $images[$i];
            $hotspotData = $hotspots[$i - 1];

            $itemData = [
                $fd->getName() . '__image' => $imageId,
                $fd->getName() . '__hotspots' => $hotspotData,
            ];

            $itemResult = $fd->getDataFromResource($itemData, $object, $params);
            $resultItems[] = $itemResult;
        }

        $imageGallery = new DataObject\Data\ImageGallery($resultItems);

        if (isset($params['owner'])) {
            $imageGallery->_setOwner($params['owner']);
            $imageGallery->_setOwnerFieldname($params['fieldname']);
            $imageGallery->_setOwnerLanguage($params['language'] ?? null);
        }

        return $imageGallery;
    }

    /**
     * @param mixed $params
     *
     * @return DataObject\Data\ImageGallery
     */
    private function createEmptyImageGallery($params = [])
    {
        $imageGallery = new DataObject\Data\ImageGallery(null);

        if (isset($params['owner'])) {
            $imageGallery->_setOwner($params['owner']);
            $imageGallery->_setOwnerFieldname($params['fieldname']);
            $imageGallery->_setOwnerLanguage($params['language'] ?? null);
        }

        return $imageGallery;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\ImageGallery $data
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
     * @param DataObject\Data\ImageGallery $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
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
     * @see Data::getDataFromEditmode
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\ImageGallery
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

        return new DataObject\Data\ImageGallery($resultItems);
    }

    /**
     * @param DataObject\Data\ImageGallery $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\ImageGallery
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\ImageGallery|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\ImageGallery) {
            return count($data->getItems()) . ' items';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\ImageGallery) {
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
     * @param DataObject\Data\ImageGallery|null $data
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
     * @param DataObject\Data\ImageGallery|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array
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

    /**
     * @param DataObject\Data\ImageGallery|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        if (empty($data)) {
            return true;
        }

        if ($data instanceof DataObject\Data\ImageGallery) {
            $items = $data->getItems();
            if (empty($items)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param DataObject\Data\ImageGallery|null $oldValue
     * @param DataObject\Data\ImageGallery|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\ImageGallery ? $oldValue->getItems() : [];
        $newValue = $newValue instanceof DataObject\Data\ImageGallery ? $newValue->getItems() : [];

        if (count($oldValue) != count($newValue)) {
            return false;
        }

        $fd = new Hotspotimage();

        foreach ($oldValue as $i => $item) {
            if (!$fd->isEqual($oldValue[$i], $newValue[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ImageGallery::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ImageGallery::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\ImageGallery::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\ImageGallery::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof Model\DataObject\Data\ImageGallery) {
            $list = [];
            $items = $value->getItems();
            $def = new Hotspotimage();
            if ($items) {
                foreach ($items as $item) {
                    if ($item instanceof DataObject\Data\Hotspotimage) {
                        $list[] = $def->normalize($item, $params);
                    }
                }
            }

            return $list;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $items = [];
            $def = new Hotspotimage();
            foreach ($value as $rawValue) {
                $items[] = $def->denormalize($rawValue, $params);
            }

            return new DataObject\Data\ImageGallery($items);
        }

        return null;
    }
}
