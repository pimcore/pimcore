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

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

class ManyToOneRelation extends AbstractRelations implements QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface
{
    use Model\DataObject\ClassDefinition\Data\Extension\Relation;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\Data\Relations\AllowObjectRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowAssetRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowDocumentRelationTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'manyToOneRelation';

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
        'type' => "enum('document','asset','object')",
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
    public $objectsAllowed = false;

    /**
     *
     * @var bool
     */
    public $assetsAllowed = false;

    /**
     * Allowed asset types
     *
     * @var array
     */
    public $assetTypes = [];

    /**
     *
     * @var bool
     */
    public $documentsAllowed = false;

    /**
     * Allowed document types
     *
     * @var array
     */
    public $documentTypes = [];

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
        return $this->documentTypes ?: [];
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
        return $this->assetTypes ?: [];
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
     * @inheritdoc
     */
    public function prepareDataForPersistence($data, $object = null, $params = [])
    {
        if ($data instanceof Element\ElementInterface) {
            $type = Element\Service::getType($data);
            $id = $data->getId();

            return [[
                'dest_id' => $id,
                'type' => $type,
                'fieldname' => $this->getName(),
            ]];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function loadData($data, $object = null, $params = [])
    {
        // data from relation table
        $data = is_array($data) ? $data : [];
        $data = current($data);

        $result = [
            'dirty' => false,
            'data' => null,
        ];

        if (!empty($data['dest_id']) && !empty($data['type'])) {
            $element = Element\Service::getElementById($data['type'], $data['dest_id']);
            if ($element instanceof Element\ElementInterface) {
                $result['data'] = $element;
            } else {
                $result['dirty'] = true;
            }
        }

        return $result;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param Asset|Document|DataObject\AbstractObject $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        $rData = $this->prepareDataForPersistence($data, $object, $params);
        $return = [];

        $return[$this->getName() . '__id'] = isset($rData[0]['dest_id']) ? $rData[0]['dest_id'] : null;
        $return[$this->getName() . '__type'] = isset($rData[0]['type']) ? $rData[0]['type'] : null;

        return $return;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param Element\AbstractElement|null $data
     * @param null|DataObject\Concrete $object
     * @param array|null $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Element\AbstractElement) {
            $r = [
                'id' => $data->getId(),
                'path' => $data->getRealFullPath(),
                'subtype' => $data->getType(),
                'type' => Element\Service::getElementType($data),
                'published' => Element\Service::isPublished($data),
            ];

            return $r;
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
     * @return Asset|Document|DataObject\AbstractObject|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if (!empty($data['id']) && !empty($data['type'])) {
            return Element\Service::getElementById($data['type'], $data['id']);
        }

        return null;
    }

    /**
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Asset|Document|DataObject\AbstractObject
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param Element\AbstractElement|null $data
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param Element\AbstractElement|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Element\AbstractElement) {
            return Element\Service::getElementType($data).' '.$data->getRealFullPath();
        }

        return '';
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
            Logger::error(sprintf('Invalid data in field `%s` [type: %s]', $this->getName(), $this->getFieldtype()));
            $allow = false;
        }

        if (!$allow) {
            throw new Element\ValidationException(sprintf('Invalid data in field `%s` [type: %s]', $this->getName(), $this->getFieldtype()), null, null);
        }
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
        if ($data instanceof Element\ElementInterface) {
            return Element\Service::getType($data).':'.$data->getRealFullPath();
        }

        return '';
    }

    /**
     * @param string $importValue
     * @param null|DataObject\Concrete $object
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
     * @param Element\AbstractElement|null $data
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
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    /**
     * converts data to be exposed via webservices
     *
     * @deprecated
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return array|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Element\ElementInterface) {
            return [
                'type' => Element\Service::getType($data),
                'subtype' => $data->getType(),
                'id' => $data->getId(),
            ];
        }

        return null;
    }

    /**
     * @deprecated
     *
     * @param mixed $value
     * @param Element\AbstractElement $relatedObject
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        }

        $value = (array) $value;
        if (array_key_exists('id', $value) and array_key_exists('type', $value)) {
            $type = $value['type'];
            $id = $value['id'];
            $el = null;

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
                    throw new \Exception('cannot get values from web service import - invalid ' . $this->getFieldtype() . ' relation');
                }
            }
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return null|Element\ElementInterface
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        if ($object instanceof DataObject\Concrete) {
            $data = $object->getObjectVar($this->getName());

            if (!$object->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($object);

                $object->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($object);
            }
        } elseif ($object instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            parent::loadLazyFieldcollectionField($object);
            $data = $object->getObjectVar($this->getName());
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            parent::loadLazyBrickField($object);
            $data = $object->getObjectVar($this->getName());
        }

        if (DataObject\AbstractObject::doHideUnpublished() && ($data instanceof Element\ElementInterface)) {
            if (!Element\Service::isPublished($data)) {
                return null;
            }
        }

        return $data;
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array|null $data
     * @param array $params
     *
     * @return mixed
     */
    public function preSetData($object, $data, $params = [])
    {
        $this->markLazyloadedFieldAsLoaded($object);

        return $data;
    }

    /**
     * @param string $assetUploadPath
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
     * @param DataObject\Concrete $object
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
     * @param DataObject\ClassDefinition\Data\ManyToOneRelation $masterDefinition
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
     * @param DataObject\Concrete $object
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
                'id' => $id,
            ];
        }
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
            $type = $value['type'];
            $id = $value['id'];

            return Element\Service::getElementById($type, $id);
        }
    }

    /**
     * @param Element\ElementInterface $value1
     * @param Element\ElementInterface $value2
     *
     * @return bool
     */
    public function isEqual($value1, $value2): bool
    {
        $value1 = $value1 ? $value1->getType() . $value1->getId() : null;
        $value2 = $value2 ? $value2->getType() . $value2->getId() : null;

        return $value1 == $value2;
    }

    /**
     * @return bool
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\Pimcore\Model\Element\AbstractElement';
    }

    /** @inheritDoc */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\Pimcore\Model\Element\AbstractElement';
    }

    /**
     * @param DataObject\Listing      $listing
     * @param Element\ElementInterface|array $data  comparison element or ['id' => <element ID>, 'type' => <element type>]
     * @param string                  $operator SQL comparison operator, currently only "=" possible
     *
     * @return DataObject\Listing
     */
    public function addListingFilter(DataObject\Listing $listing, $data, $operator = '=')
    {
        if ($data instanceof Element\ElementInterface) {
            $data = [
                'id' => $data->getId(),
                'type' => Element\Service::getElementType($data),
            ];
        }

        if (!isset($data['id'], $data['type'])) {
            throw new \InvalidArgumentException('Please provide an array with keys "id" and "type" or an object which implements '.Element\ElementInterface::class);
        }

        if ($operator === '=') {
            $listing->addConditionParam('`'.$this->getName().'__id` = ? AND `'.$this->getName().'__type` = ?', [$data['id'], $data['type']]);

            return $listing;
        }
        throw new \InvalidArgumentException('Filtering '.__CLASS__.' does only support "=" operator');
    }

    /**
     * @return string|null
     */
    public function getPhpdocReturnType(): ?string
    {
        if ($this->getPhpdocType()) {
            return $this->getPhpdocType() . '|null';
        }

        return null;
    }
}

class_alias(ManyToOneRelation::class, 'Pimcore\Model\DataObject\ClassDefinition\Data\Href');
