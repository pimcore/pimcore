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

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;

class ManyToOneRelation extends AbstractRelations implements QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, VarExporterInterface, NormalizerInterface
{
    use Model\DataObject\ClassDefinition\Data\Extension\Relation;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\Data\Relations\AllowObjectRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowAssetRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowDocumentRelationTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'manyToOneRelation';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string
     */
    public $assetUploadPath;

    /**
     * @internal
     *
     * @var bool
     */
    public $relationType = true;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = [
        'id' => 'int(11)',
        'type' => "enum('document','asset','object')",
    ];

    /**
     * @internal
     *
     * @var bool
     */
    public $objectsAllowed = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $assetsAllowed = false;

    /**
     * Allowed asset types
     *
     * @internal
     *
     * @var array
     */
    public $assetTypes = [];

    /**
     * @internal
     *
     * @var bool
     */
    public $documentsAllowed = false;

    /**
     * Allowed document types
     *
     * @internal
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
     * {@inheritdoc}
     */
    protected function prepareDataForPersistence($data, $object = null, $params = [])
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
     * {@inheritdoc}
     */
    protected function loadData(array $data, $object = null, $params = [])
    {
        // data from relation table
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
     * @param Element\ElementInterface|null $data
     * @param null|DataObject\Concrete $object
     * @param array|null $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Element\ElementInterface) {
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
     * @param Element\ElementInterface|null $data
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
     * @param Element\ElementInterface|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Element\ElementInterface) {
            return Element\Service::getElementType($data).' '.$data->getRealFullPath();
        }

        return '';
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
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
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
     * {@inheritdoc}
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
     * @param Element\ElementInterface|null $data
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

        if (DataObject::doHideUnpublished() && ($data instanceof Element\ElementInterface)) {
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

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function getPhpdocType()
    {
        return implode(' | ', $this->getPhpDocClassString(false));
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value) {
            $type = Element\Service::getType($value);
            $id = $value->getId();

            return [
                'type' => $type,
                'id' => $id,
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $type = $value['type'];
            $id = $value['id'];

            return Element\Service::getElementById($type, $id);
        }

        return null;
    }

    /**
     * @param Element\ElementInterface|null $value1
     * @param Element\ElementInterface|null $value2
     *
     * @return bool
     */
    public function isEqual($value1, $value2): bool
    {
        $value1 = $value1 ? $value1->getType() . $value1->getId() : null;
        $value2 = $value2 ? $value2->getType() . $value2->getId() : null;

        return $value1 === $value2;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Element\AbstractElement::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Element\AbstractElement::class;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        if ($this->getPhpdocType()) {
            return '\\' . $this->getPhpdocType() . '|null';
        }

        return null;
    }
}
