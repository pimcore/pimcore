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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;

class ManyToManyObjectRelation extends AbstractRelations implements QueryResourcePersistenceAwareInterface, OptimizedAdminLoadingInterface, TypeDeclarationSupportInterface, VarExporterInterface, NormalizerInterface, IdRewriterInterface, PreGetDataInterface, PreSetDataInterface, LayoutDefinitionEnrichmentInterface
{
    use Model\DataObject\ClassDefinition\Data\Extension\Relation;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\Data\Relations\AllowObjectRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\ManyToManyRelationTrait;
    use DataObject\ClassDefinition\Data\Extension\RelationFilterConditionParser;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'manyToManyObjectRelation';

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
     * @var int|null
     */
    public $maxItems;

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
     * @var string|null
     */
    public $visibleFields;

    /**
     * @internal
     *
     * @var bool
     */
    public $allowToCreateNewObject = true;

    /**
     * @internal
     *
     * @var bool
     */
    public $optimizedAdminLoading = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $enableTextSelection = false;

    /**
     * @internal
     *
     * @var array
     */
    public $visibleFieldDefinitions = [];

    /**
     * @return bool
     */
    public function getObjectsAllowed()
    {
        return true;
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
                if ($object instanceof DataObject\Concrete) {
                    $return[] = [
                        'dest_id' => $object->getId(),
                        'type' => 'object',
                        'fieldname' => $this->getName(),
                        'index' => $counter,
                    ];
                }
                $counter++;
            }

            return $return;
        } elseif (is_array($data) && count($data) === 0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null - this indicates data was not loaded
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData(array $data, $object = null, $params = [])
    {
        $objects = [
            'dirty' => false,
            'data' => [],
        ];
        foreach ($data as $relation) {
            $o = DataObject::getById($relation['dest_id']);
            if ($o instanceof DataObject\Concrete) {
                $objects['data'][] = $o;
            } else {
                $objects['dirty'] = true;
            }
        }
        //must return array - otherwise this means data is not loaded
        return $objects;
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

        $ids = [];

        if (is_array($data)) {
            foreach ($data as $relation) {
                if ($relation instanceof DataObject\Concrete) {
                    $ids[] = $relation->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        }

        throw new \Exception('invalid data passed to getDataForQueryResource - must be array and it is: ' . print_r($data, true));
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $return = [];

        $visibleFieldsArray = $this->getVisibleFields() ? explode(',', $this->getVisibleFields()) : [];

        $gridFields = (array)$visibleFieldsArray;

        // add data
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $referencedObject) {
                if ($referencedObject instanceof DataObject\Concrete) {
                    $return[] = DataObject\Service::gridObjectData($referencedObject, $gridFields, null, ['purpose' => 'editmode']);
                }
            }
        }

        return $return;
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

        $objects = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                $o = DataObject::getById($object['id']);
                if ($o) {
                    $objects[] = $o;
                }
            }
        }
        //must return array if data shall be set
        return $objects;
    }

    /**
     * @see Data::getDataFromEditmode
     *
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
     * @param DataObject\Concrete|null $object
     * @param mixed $params
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
            foreach ($data as $o) {
                if ($o instanceof Element\ElementInterface) {
                    $paths[] = $o->getRealFullPath();
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
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (is_array($data)) {
            $this->performMultipleAssignmentCheck($data);

            foreach ($data as $o) {
                if (empty($o)) {
                    continue;
                }

                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass || !($o instanceof DataObject\Concrete)) {
                    if (!$allowClass && $o instanceof DataObject\Concrete) {
                        $id = $o->getId();
                    } else {
                        $id = '??';
                    }

                    throw new Element\ValidationException('Invalid object relation to object ['.$id.'] in field ' . $this->getName(). ' , tried to assign ' . $o->getId());
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
                    $paths[] = $eo->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    /**
     * @param DataObject\AbstractObject[]|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $o) {
                if ($o instanceof DataObject\AbstractObject) {
                    $dependencies['object_' . $o->getId()] = [
                        'id' => $o->getId(),
                        'type' => 'object',
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param mixed $container
     * @param array $params
     *
     * @return array
     */
    public function preGetData(/** mixed */ $container, /** array */ $params = []) // : mixed
    {
        $data = null;
        if ($container instanceof DataObject\Concrete) {
            $data = $container->getObjectVar($this->getName());
            if (!$container->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($container);

                $container->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($container);
            }
        } elseif ($container instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
            parent::loadLazyFieldcollectionField($container);
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
            parent::loadLazyBrickField($container);
            $data = $container->getObjectVar($this->getName());
        }

        if (DataObject::doHideUnpublished() && is_array($data)) {
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
     * { @inheritdoc }
     */
    public function preSetData(/** mixed */ $container, /**  mixed */ $data, /** array */ $params = []) // : mixed
    {
        if ($data === null) {
            $data = [];
        }

        $this->markLazyloadedFieldAsLoaded($container);

        return $data;
    }

    /**
     * @param int|null $maxItems
     *
     * @return $this
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxItems()
    {
        return $this->maxItems;
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
     * @param Element\ElementInterface[]|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array
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
     * { @inheritdoc }
     */
    public function rewriteIds(/** mixed */ $container, /** array */ $idMapping, /** array */ $params = []) /** :mixed */
    {
        $data = $this->getDataFromObjectParam($container, $params);
        $data = $this->rewriteIdsService($data, $idMapping);

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data\ManyToManyObjectRelation $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->maxItems = $masterDefinition->maxItems;
        $this->relationType = $masterDefinition->relationType;
    }

    /**
     * {@inheritdoc}
     */
    public function enrichLayoutDefinition(/* ?Concrete */ $object, /* array */ $context = []) // : static
    {
        if (!$this->visibleFields) {
            return $this;
        }

        $classIds = $this->getClasses();

        if (empty($classIds[0]['classes'])) {
            return $this;
        }

        $classId = $classIds[0]['classes'];

        if (is_numeric($classId)) {
            $class = DataObject\ClassDefinition::getById($classId);
        } else {
            $class = DataObject\ClassDefinition::getByName($classId);
        }

        if (!$class) {
            return $this;
        }
        
        if (!isset($context['purpose'])) {
            $context['purpose'] = 'layout';
        }

        $this->visibleFieldDefinitions = [];

        $translator = \Pimcore::getContainer()->get('translator');

        $visibleFields = explode(',', $this->visibleFields);
        foreach ($visibleFields as $field) {
            $fd = $class->getFieldDefinition($field, $context);

            if (!$fd) {
                $fieldFound = false;
                /** @var Localizedfields|null $localizedfields */
                $localizedfields = $class->getFieldDefinitions($context)['localizedfields'] ?? null;
                if ($localizedfields) {
                    if ($fd = $localizedfields->getFieldDefinition($field)) {
                        $this->visibleFieldDefinitions[$field]['name'] = $fd->getName();
                        $this->visibleFieldDefinitions[$field]['title'] = $fd->getTitle();
                        $this->visibleFieldDefinitions[$field]['fieldtype'] = $fd->getFieldType();

                        if ($fd instanceof DataObject\ClassDefinition\Data\Select || $fd instanceof DataObject\ClassDefinition\Data\Multiselect) {
                            $this->visibleFieldDefinitions[$field]['options'] = $fd->getOptions();
                        }

                        $fieldFound = true;
                    }
                }

                if (!$fieldFound) {
                    $this->visibleFieldDefinitions[$field]['name'] = $field;
                    $this->visibleFieldDefinitions[$field]['title'] = $translator->trans($field, [], 'admin');
                    $this->visibleFieldDefinitions[$field]['fieldtype'] = 'input';
                }
            } else {
                $this->visibleFieldDefinitions[$field]['name'] = $fd->getName();
                $this->visibleFieldDefinitions[$field]['title'] = $fd->getTitle();
                $this->visibleFieldDefinitions[$field]['fieldtype'] = $fd->getFieldType();
                $this->visibleFieldDefinitions[$field]['noteditable'] = true;

                if (
                    $fd instanceof DataObject\ClassDefinition\Data\Select
                    || $fd instanceof DataObject\ClassDefinition\Data\Multiselect
                    || $fd instanceof DataObject\ClassDefinition\Data\BooleanSelect
                ) {
                    if (
                        $fd instanceof DataObject\ClassDefinition\Data\Select
                        || $fd instanceof DataObject\ClassDefinition\Data\Multiselect
                    ) {
                        $this->visibleFieldDefinitions[$field]['optionsProviderClass'] = $fd->getOptionsProviderClass();
                    }

                    $this->visibleFieldDefinitions[$field]['options'] = $fd->getOptions();
                }
            }
        }

        return $this;
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
                $type = Element\Service::getElementType($element);
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

    /**
     * {@inheritdoc}
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
     * @internal
     *
     * @param array $item
     *
     * @return string
     */
    protected function buildUniqueKeyForDiffEditor($item)
    {
        return $item['id'];
    }

    /**
     * @internal
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
                    $item['id'] = $in['id'];
                    $item['path'] = $in['fullpath'];
                    $item['type'] = $in['type'];

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
     *
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
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

        return null;
    }

    /**
     * @param array|string|null $visibleFields
     *
     * @return $this
     */
    public function setVisibleFields($visibleFields)
    {
        if (is_array($visibleFields) && count($visibleFields)) {
            $visibleFields = implode(',', $visibleFields);
        }
        $this->visibleFields = $visibleFields;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVisibleFields()
    {
        return $this->visibleFields;
    }

    /**
     * @return bool
     */
    public function isAllowToCreateNewObject(): bool
    {
        return $this->allowToCreateNewObject;
    }

    /**
     * @param bool $allowToCreateNewObject
     */
    public function setAllowToCreateNewObject($allowToCreateNewObject)
    {
        $this->allowToCreateNewObject = (bool)$allowToCreateNewObject;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptimizedAdminLoading(): bool
    {
        return (bool) $this->optimizedAdminLoading;
    }

    /**
     * @param bool $optimizedAdminLoading
     */
    public function setOptimizedAdminLoading($optimizedAdminLoading)
    {
        $this->optimizedAdminLoading = $optimizedAdminLoading;
    }

    /**
     * @return bool
     */
    public function isEnableTextSelection(): bool
    {
        return $this->enableTextSelection;
    }

    /**
     * @param bool $enableTextSelection
     */
    public function setEnableTextSelection(bool $enableTextSelection): void
    {
        $this->enableTextSelection = $enableTextSelection;
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
        if ($data instanceof DataObject\Concrete) {
            $data = $data->getId();
        }

        if ($operator === '=') {
            $listing->addConditionParam('`'.$this->getName().'` LIKE ?', '%,'.$data.',%');

            return $listing;
        }

        return parent::addListingFilter($listing, $data, $operator);
    }

    /**
     * Filter by relation feature
     *
     * @param array|string|null $value
     * @param string            $operator
     * @param array             $params
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $name = $params['name'] ?: $this->name;

        return $this->getRelationFilterCondition($value, $operator, $name);
    }
}
