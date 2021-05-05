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
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;

class ManyToManyRelation extends AbstractRelations implements QueryResourcePersistenceAwareInterface, OptimizedAdminLoadingInterface, TypeDeclarationSupportInterface, VarExporterInterface, NormalizerInterface
{
    use Model\DataObject\ClassDefinition\Data\Extension\Relation;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\Data\Relations\AllowObjectRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowAssetRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowDocumentRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\ManyToManyRelationTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'manyToManyRelation';

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
     * @var int
     */
    public $maxItems;

    /**
     * @internal
     *
     * @var string
     */
    public $assetUploadPath;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'text';

    /**
     * @internal
     *
     * @var bool
     */
    public $relationType = true;

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
     * {@inheritdoc}
     */
    protected function prepareDataForPersistence($data, $object = null, $params = [])
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $object) {
                if ($object instanceof Element\ElementInterface) {
                    $return[] = [
                        'dest_id' => $object->getId(),
                        'type' => Element\Service::getElementType($object),
                        'fieldname' => $this->getName(),
                        'index' => $counter,
                    ];
                }
                $counter++;
            }

            return $return;
        } elseif (is_array($data) and count($data) === 0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null  - this indicates data was not loaded
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData(array $data, $object = null, $params = [])
    {
        $elements = [
            'dirty' => false,
            'data' => [],
        ];
        foreach ($data as $element) {
            $e = null;
            if ($element['type'] === 'object') {
                $e = DataObject::getById($element['dest_id']);
            } elseif ($element['type'] === 'asset') {
                $e = Asset::getById($element['dest_id']);
            } elseif ($element['type'] === 'document') {
                $e = Document::getById($element['dest_id']);
            }

            if ($e instanceof Element\ElementInterface) {
                $elements['data'][] = $e;
            } else {
                $elements['dirty'] = true;
            }
        }
        //must return array - otherwise this means data is not loaded
        return $elements;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @throws \Exception
     *
     * @return string|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        //return null when data is not set
        if (!$data) {
            return null;
        }

        $d = [];

        if (is_array($data)) {
            foreach ($data as $element) {
                if ($element instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($element);
                    $d[] = $elementType . '|' . $element->getId();
                }
            }

            return ',' . implode(',', $d) . ',';
        }

        throw new \Exception('invalid data passed to getDataForQueryResource - must be array');
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if ($element instanceof DataObject\Concrete) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), DataObject::OBJECT_TYPE_OBJECT, $element->getClassName(), $element->getPublished()];
                } elseif ($element instanceof DataObject\AbstractObject) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER];
                } elseif ($element instanceof Asset) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'asset', $element->getType()];
                } elseif ($element instanceof Document) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'document', $element->getType(), $element->getPublished()];
                }
            }
            if (empty($return)) {
                $return = null;
            }

            return $return;
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array|null|false $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        //if not set, return null
        if ($data === null || $data === false) {
            return null;
        }

        $elements = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                $e = null;
                if ($element['type'] == 'object') {
                    $e = DataObject::getById($element['id']);
                } elseif ($element['type'] == 'asset') {
                    $e = Asset::getById($element['id']);
                } elseif ($element['type'] == 'document') {
                    $e = Document::getById($element['id']);
                }

                if ($e instanceof Element\ElementInterface) {
                    $elements[] = $e;
                }
            }
        }
        //must return array if data shall be set
        return $elements;
    }

    /**
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param array|null $data
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     *
     * @todo: $pathes is undefined
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param Element\ElementInterface[]|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if (is_array($data) && count($data) > 0) {
            $paths = [];

            foreach ($data as $element) {
                if ($element instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getElementType($element) .' '. $element->getRealFullPath();
                }
            }

            return implode('<br />', $paths);
        }

        return null;
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
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        $allow = true;
        if (is_array($data)) {
            $this->performMultipleAssignmentCheck($data);
            foreach ($data as $d) {
                if ($d instanceof Document) {
                    $allow = $this->allowDocumentRelation($d);
                } elseif ($d instanceof Asset) {
                    $allow = $this->allowAssetRelation($d);
                } elseif ($d instanceof DataObject\AbstractObject) {
                    $allow = $this->allowObjectRelation($d);
                } elseif (empty($d)) {
                    $allow = true;
                } else {
                    $allow = false;
                }
                if (!$allow) {
                    throw new Element\ValidationException(sprintf('Invalid relation in field `%s` [type: %s]', $this->getName(), $this->getFieldtype()), null, null);
                }
            }

            if ($this->getMaxItems() && count($data) > $this->getMaxItems()) {
                throw new Element\ValidationException('Number of allowed relations in field `' . $this->getName() . '` exceeded (max. ' . $this->getMaxItems() . ')');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $paths = [];
            foreach ($data as $eo) {
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getType($eo) . ':' . $eo->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
    {
        return $tags;
    }

    /**
     * @param Element\ElementInterface[]|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $e) {
                if ($e instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($e);
                    $dependencies[$elementType . '_' . $e->getId()] = [
                        'id' => $e->getId(),
                        'type' => $elementType,
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return array
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

                if ($object instanceof Element\DirtyIndicatorInterface) {
                    $object->markFieldDirty($this->getName(), false);
                }
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

        if (DataObject::doHideUnpublished() and is_array($data)) {
            $publishedList = [];
            foreach ($data as $listElement) {
                if (Element\Service::isPublished($listElement)) {
                    $publishedList[] = $listElement;
                }
            }

            return $publishedList;
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array|null $data
     * @param array $params
     *
     * @return array|null
     */
    public function preSetData($object, $data, $params = [])
    {
        if ($data === null) {
            $data = [];
        }

        $this->markLazyloadedFieldAsLoaded($object);

        return $data;
    }

    /**
     * @param string|int|null $maxItems
     *
     * @return $this
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
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

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param array|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        $value = [];
        $value['type'] = 'html';
        $value['html'] = '';

        if ($data) {
            $html = $this->getVersionPreview($data, $object, $params);
            $value['html'] = $html;
        }

        return $value;
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
     * @return Element\ElementInterface[]
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        $data = $this->rewriteIdsService($data, $idMapping);

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data\ManyToManyRelation $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->maxItems = $masterDefinition->maxItems;
        $this->assetUploadPath = $masterDefinition->assetUploadPath;
        $this->relationType = $masterDefinition->relationType;
        $this->objectsAllowed = $masterDefinition->objectsAllowed;
        $this->assetsAllowed = $masterDefinition->assetsAllowed;
        $this->assetTypes = $masterDefinition->assetTypes;
        $this->documentsAllowed = $masterDefinition->documentsAllowed;
        $this->documentTypes = $masterDefinition->documentTypes;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPhpdocType()
    {
        return implode(' | ', $this->getPhpDocClassString(true));
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $element) {
                $type = Element\Service::getType($element);
                $id = $element->getId();
                $result[] = [
                    'type' => $type,
                    'id' => $id,
                ];
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     *
     * @param mixed $value
     * @param mixed $params
     *
     * @return mixed
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $elementData) {
                $type = $elementData['type'];
                $id = $elementData['id'];
                $element = Element\Service::getElementById($type, $id);
                if ($element) {
                    $result[] = $element;
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * Returns a ID which must be unique across the grid rows
     *
     * @param array $item
     *
     * @return string
     */
    public function buildUniqueKeyForDiffEditor($item)
    {
        $parts = [
            $item['id'],
            $item['path'],
            $item['type'],
            $item['subtype'],
        ];

        return json_encode($parts);
    }

    /**
     * {@inheritdoc}
     */
    protected function processDiffDataForEditMode($originalData, $data, $object = null, $params = [])
    {
        if ($data) {
            $data = $data[0];

            $items = $data['data'];
            $newItems = [];
            if ($items) {
                foreach ($items as $in) {
                    $item = [];
                    $item['id'] = $in[0];
                    $item['path'] = $in[1];
                    $item['type'] = $in[2];
                    $item['subtype'] = $in[3];

                    $unique = $this->buildUniqueKeyForDiffEditor($item);

                    $itemId = json_encode($item);
                    $raw = $itemId;

                    $newItems[] = [
                        'itemId' => $itemId,
                        'title' => $item['path'],
                        'raw' => $raw,
                        'gridrow' => $item,
                        'unique' => $unique,
                    ];
                }
                $data['data'] = $newItems;
            }

            $data['value'] = [
                'type' => 'grid',
                'columnConfig' => [
                    'id' => [
                        'width' => 60,
                    ],
                    'path' => [
                        'flex' => 2,
                    ],

                ],
                'html' => $this->getVersionPreview($originalData, $object, $params),
            ];

            $newData = [];
            $newData[] = $data;

            return $newData;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $originalData = $data;
        $data = parent::getDiffDataForEditMode($data, $object, $params);
        $data = $this->processDiffDataForEditMode($originalData, $data, $object, $params);

        return $data;
    }

    /** See parent class.
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            $tabledata = $data[0]['data'];

            $result = [];
            if ($tabledata) {
                foreach ($tabledata as $in) {
                    $out = json_decode($in['raw'], true);
                    $result[] = $out;
                }
            }

            return $this->getDataFromEditmode($result, $object, $params);
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptimizedAdminLoading(): bool
    {
        return true;
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
            $listing->addConditionParam('`'.$this->getName().'` LIKE ?', '%,'.$data['type'].'|'.$data['id'].',%');

            return $listing;
        }

        throw new \InvalidArgumentException('Filtering '.__CLASS__.' does only support "=" operator');
    }
}
