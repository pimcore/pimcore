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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\DataObject;

class Href extends Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations
{
    use Model\DataObject\ClassDefinition\Data\Extension\Relation;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'href';

    /**
     * @var int
     */
    public $width;

    /**
     * @var string
     */
    public $assetUploadPath;

    /**
     * @var bool
     */
    public $relationType = true;

    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = [
        'id' => 'int(11)',
        'type' => "enum('document','asset','object')"
    ];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\Document\\Page | \\Pimcore\\Model\\Document\\Snippet | \\Pimcore\\Model\\Document | \\Pimcore\\Model\\Asset | \\Pimcore\\Model\\DataObject\\AbstractObject';

    /**
     *
     * @var bool
     */
    public $objectsAllowed;

    /**
     *
     * @var bool
     */
    public $assetsAllowed;

    /**
     * Allowed asset types
     *
     * @var array
     */
    public $assetTypes;

    /**
     *
     * @var bool
     */
    public $documentsAllowed;

    /**
     * Allowed document types
     *
     * @var array
     */
    public $documentTypes;

    /**
     * @return bool
     */
    public function getObjectsAllowed()
    {
        return $this->objectsAllowed;
    }

    /**
     * @param bool $objectsAllowed
     *
     * @return $this
     */
    public function setObjectsAllowed($objectsAllowed)
    {
        $this->objectsAllowed = $objectsAllowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDocumentsAllowed()
    {
        return $this->documentsAllowed;
    }

    /**
     * @param bool $documentsAllowed
     *
     * @return $this
     */
    public function setDocumentsAllowed($documentsAllowed)
    {
        $this->documentsAllowed = $documentsAllowed;

        return $this;
    }

    /**
     * @return array
     */
    public function getDocumentTypes()
    {
        return $this->documentTypes;
    }

    /**
     * @param array $documentTypes
     *
     * @return $this
     */
    public function setDocumentTypes($documentTypes)
    {
        $this->documentTypes = Element\Service::fixAllowedTypes($documentTypes, 'documentTypes');

        return $this;
    }

    /**
     *
     * @return bool
     */
    public function getAssetsAllowed()
    {
        return $this->assetsAllowed;
    }

    /**
     *
     * @param bool $assetsAllowed
     *
     * @return $this
     */
    public function setAssetsAllowed($assetsAllowed)
    {
        $this->assetsAllowed = $assetsAllowed;

        return $this;
    }

    /**
     * @return array
     */
    public function getAssetTypes()
    {
        return $this->assetTypes;
    }

    /**
     * @param array $assetTypes
     *
     * @return $this
     */
    public function setAssetTypes($assetTypes)
    {
        $this->assetTypes = Element\Service::fixAllowedTypes($assetTypes, 'assetTypes');

        return $this;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForResource
     *
     * @param Asset | Document | DataObject\AbstractObject $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Element\ElementInterface) {
            $type =  Element\Service::getType($data);
            $id = $data->getId();

            return [[
                'dest_id' => $id,
                'type' => $type,
                'fieldname' => $this->getName()
            ]];
        } else {
            return null;
        }
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataFromResource
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param bool $notRelationTable
     *
     * @return Asset|Document|DataObject\AbstractObject
     */
    public function getDataFromResource($data, $object = null, $params = [], $notRelationTable = false)
    {
        // data from relation table
        $data = is_array($data) ? $data : [];
        $data = current($data);

        if ($data['dest_id'] && $data['type']) {
            return Element\Service::getElementById($data['type'], $data['dest_id']);
        }

        return null;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForQueryResource
     *
     * @param Asset|Document|DataObject\AbstractObject $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        $rData = $this->getDataForResource($data, $object, $params);

        $return = [];
        $return[$this->getName() . '__id'] = $rData[0]['dest_id'];
        $return[$this->getName() . '__type'] = $rData[0]['type'];

        return $return;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForEditmode
     *
     * @param Asset|Document|DataObject\AbstractObject $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Element\ElementInterface) {
            $r = [
                'id' => $data->getId(),
                'path' => $data->getRealFullPath(),
                'subtype' => $data->getType(),
                'type' => Element\Service::getElementType($data)
            ];

            return $r;
        }

        return;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return Asset|Document|DataObject\AbstractObject
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['id'] && $data['type']) {
            return Element\Service::getElementById($data['type'], $data['id']);
        }

        return null;
    }

    /**
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return Asset|Document|DataObject\AbstractObject
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param $data
     * @param null $object
     * @param array $params
     *
     * @return string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getVersionPreview
     *
     * @param Document | Asset | DataObject\AbstractObject $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Element\ElementInterface) {
            return $data->getRealFullPath();
        }
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
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if ($data instanceof Document) {
            $allow = $this->allowDocumentRelation($data);
        } elseif ($data instanceof Asset) {
            $allow = $this->allowAssetRelation($data);
        } elseif ($data instanceof DataObject\AbstractObject) {
            $allow = $this->allowObjectRelation($data);
        } elseif (empty($data)) {
            $allow = true;
        } else {
            Logger::error('invalid data in href');
            $allow = false;
        }

        if (!$allow) {
            throw new \Exception('Invalid href relation', null, null);
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
        if ($data instanceof Element\ElementInterface) {
            return Element\Service::getType($data).':'.$data->getRealFullPath();
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed|null|Asset|Document|Element\ElementInterface
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = null;

        $values = explode(':', $importValue);
        if (count($values) == 2) {
            $type = $values[0];
            $path = $values[1];
            $value = Element\Service::getElementByPath($type, $path);
        } else {
            //fallback for old export files
            if ($el = Asset::getByPath($importValue)) {
                $value = $el;
            } elseif ($el = Document::getByPath($importValue)) {
                $value = $el;
            } elseif ($el = DataObject::getByPath($importValue)) {
                $value = $el;
            }
        }

        return $value;
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

        if ($this->getLazyLoading()) {
            return $tags;
        }

        if ($data instanceof Element\ElementInterface) {
            if (!array_key_exists($data->getCacheTag(), $tags)) {
                $tags = $data->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof Element\ElementInterface) {
            $elementType = Element\Service::getElementType($data);
            $dependencies[$elementType . '_' . $data->getId()] = [
                'id' => $data->getId(),
                'type' => $elementType
            ];
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
        if ($data instanceof Element\ElementInterface) {
            return [
                'type' => Element\Service::getType($data),
                'subtype' => $data->getType(),
                'id' => $data->getId()
            ];
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } else {
            $value = (array) $value;
            if (array_key_exists('id', $value) and array_key_exists('type', $value)) {
                $type = $value['type'];
                $id = $value['id'];

                if ($idMapper) {
                    $id = $idMapper->getMappedId($type, $id);
                }

                if ($id) {
                    $el = Element\Service::getElementById($type, $id);
                }

                if ($el instanceof Element\ElementInterface) {
                    return $el;
                } else {
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure('object', $relatedObject->getId(), $type, $value['id']);
                    } else {
                        throw new \Exception('cannot get values from web service import - invalid href relation');
                    }
                }
            } else {
                throw new \Exception('cannot get values from web service import - invalid data');
            }
        }
    }

    /**
     * @param $object
     * @param array $params
     *
     * @return null|DataObject\Fieldcollection\Data\AbstractData|DataObject\Concrete|DataObject\Objectbrick\Data\
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        if ($object instanceof DataObject\Concrete) {
            $data = $object->{$this->getName()};

            if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
                $data = $this->load($object, ['force' => true]);

                $setter = 'set' . ucfirst($this->getName());
                if (method_exists($object, $setter)) {
                    $object->$setter($data);
                }
            }
        } elseif ($object instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $data = $object->{$this->getName()};
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $data = $object->{$this->getName()};
        }

        if (DataObject\AbstractObject::doHideUnpublished() and ($data instanceof Element\ElementInterface)) {
            if (!Element\Service::isPublished($data)) {
                return null;
            }
        }

        return $data;
    }

    /**
     * @param $object
     * @param $data
     * @param array $params
     *
     * @return mixed
     */
    public function preSetData($object, $data, $params = [])
    {
        if ($object instanceof DataObject\Concrete) {
            if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
                $object->addO__loadedLazyField($this->getName());
            }
        }

        return $data;
    }

    /**
     * @param $assetUploadPath
     *
     * @return $this
     */
    public function setAssetUploadPath($assetUploadPath)
    {
        $this->assetUploadPath = $assetUploadPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getAssetUploadPath()
    {
        return $this->assetUploadPath;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
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
        if ($data) {
            $data = $this->rewriteIdsService([$data], $idMapping);
            $data = $data[0]; //get the first element
        }

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->assetUploadPath = $masterDefinition->assetUploadPath;
        $this->relationType = $masterDefinition->relationType;
    }

    /**
     * @return string
     */
    public function getPhpdocType()
    {
        return implode(' | ', $this->getPhpDocClassString(false));
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
            $type = Element\Service::getType($value);
            $id = $value->getId();

            return [
                'type' => $type,
                'id' => $id
            ];
        }
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
            $type = $value['type'];
            $id = $value['id'];

            return Element\Service::getElementById($type, $id);
        }
    }
}
