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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element;

class Image extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use Model\DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'image';

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
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'int(11)';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'int(11)';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\Asset\\Image';

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
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
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param Asset $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param int $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Asset|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (intval($data) > 0) {
            return Asset\Image::getById($data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param Asset $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param Asset\Image|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Asset\Image) {
            return $data->getObjectVars();
        }

        return $data;
    }

    /**
     * @param Asset $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Asset\Image|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data && intval($data['id']) > 0) {
            return Asset\Image::getById($data['id']);
        }

        return null;
    }

    /**
     * @param int $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Asset
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param Asset\Image|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail?id=' . $data->getId() . '&width=100&height=100&aspectratio=true" />';
        }

        return null;
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Element\ElementInterface) {
            return $data->getRealFullPath();
        }

        return '';
    }

    /**
     * @param string $importValue
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed|null|Asset
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = null;
        if ($el = Asset::getByPath($importValue)) {
            $value = $el;
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * @param Model\DataObject\Concrete|Model\DataObject\Localizedfield|Model\DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $object
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

        if ($data instanceof Asset\Image) {
            if (!array_key_exists($data->getCacheTag(), $tags)) {
                $tags = $data->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param Asset|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof Asset) {
            $dependencies['asset_' . $data->getId()] = [
                'id' => $data->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    /**
     * converts data to be exposed via webservices
     *
     * @deprecated
     *
     * @param Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Asset) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @deprecated
     *
     * @param mixed $value
     * @param Model\DataObject\Concrete $object
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return null|Asset|Asset\Archive|Asset\Audio|Asset\Document|Asset\Folder|Asset\Image|Asset\Text|Asset\Unknown|Asset\Video
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        $id = $value;

        $fromMapper = false;
        if ($idMapper && !empty($value)) {
            $id = $idMapper->getMappedId('asset', $value);
            $fromMapper = true;
        }

        $asset = Asset::getById($id);
        if (empty($id) && !$fromMapper) {
            return null;
        } elseif (is_numeric($value) and $asset instanceof Asset) {
            return $asset;
        } else {
            if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                throw new \Exception('cannot get values from web service import - invalid data, referencing unknown asset with id [ '.$value.' ]');
            } else {
                $idMapper->recordMappingFailure('object', $object->getId(), 'asset', $value);
            }
        }

        return null;
    }

    /**
     * @param string $uploadPath
     *
     * @return $this
     */
    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = $uploadPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    /** True if change is allowed in edit mode.
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param Asset\Image|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        $versionPreview = null;
        if ($data instanceof Asset\Image) {
            $versionPreview = '/admin/asset/get-image-thumbnail?id=' . $data->getId() . '&width=150&height=150&aspectratio=true';
        }

        if ($versionPreview) {
            $value = [];
            $value['src'] = $versionPreview;
            $value['type'] = 'img';

            return $value;
        } else {
            return '';
        }
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
        if ($data instanceof Asset\Image) {
            if (array_key_exists('asset', $idMapping) and array_key_exists($data->getId(), $idMapping['asset'])) {
                return Asset::getById($idMapping['asset'][$data->getId()]);
            }
        }

        return $data;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\Image $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->uploadPath = $masterDefinition->uploadPath;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof \Pimcore\Model\Asset\Image) {
            return [
                'type' => 'asset',
                'id' => $value->getId(),
            ];
        }
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        $id = $value['id'];
        if (intval($id) > 0) {
            return Asset\Image::getById($id);
        }
    }

    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @param Asset|null $oldValue
     * @param Asset|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = $oldValue instanceof Asset ? $oldValue->getId() : null;
        $newValue = $newValue instanceof Asset ? $newValue->getId() : null;

        return $oldValue === $newValue;
    }
}
