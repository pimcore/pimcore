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

use Pimcore\Db;
use Pimcore\Element\MarshallerService;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Block extends Data implements CustomResourcePersistingInterface, ResourcePersistenceAwareInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface, VarExporterInterface, NormalizerInterface
{
    use Element\ChildsCompatibilityTrait;
    use Extension\ColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'block';

    /**
     * @internal
     *
     * @var bool
     */
    public $lazyLoading;

    /**
     * @internal
     *
     * @var bool
     */
    public $disallowAddRemove;

    /**
     * @internal
     *
     * @var bool
     */
    public $disallowReorder;

    /**
     * @internal
     *
     * @var bool
     */
    public $collapsible;

    /**
     * @internal
     *
     * @var bool
     */
    public $collapsed;

    /**
     * @internal
     *
     * @var int
     */
    public $maxItems;

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'longtext';

    /**
     * @internal
     *
     * @var string
     */
    public $styleElement = '';

    /**
     * @internal
     *
     * @var array
     */
    public $childs = [];

    /**
     * @internal
     *
     * @var array|null
     */
    public $layout;

    /**
     * contains further child field definitions if there are more than one localized fields in on class
     *
     * @internal
     *
     * @var array
     */
    protected $referencedFields = [];

    /**
     * @internal
     *
     * @var array|null
     */
    public $fieldDefinitionsCache;

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $result = [];

        if (is_array($data)) {
            foreach ($data as $blockElements) {
                $resultElement = [];

                /** @var DataObject\Data\BlockElement $blockElement */
                foreach ($blockElements as $elementName => $blockElement) {
                    $this->setBlockElementOwner($blockElement, $params);

                    $fd = $this->getFieldDefinition($elementName);
                    if (!$fd) {
                        // class definition seems to have changed
                        Logger::warn('class definition seems to have changed, element name: ' . $elementName);
                        continue;
                    }
                    $elementData = $blockElement->getData();

                    // $encodedDataBC = $fd->marshal($elementData, $object, ['raw' => true, 'blockmode' => true]);

                    if ($fd instanceof NormalizerInterface) {
                        $normalizedData = $fd->normalize($elementData, [
                            'object' => $object,
                            'fieldDefinition' => $fd,
                        ]);
                        $encodedData = $normalizedData;

                        /** @var MarshallerService $marshallerService */
                        $marshallerService = \Pimcore::getContainer()->get(MarshallerService::class);

                        if ($marshallerService->supportsFielddefinition('block', $fd->getFieldtype())) {
                            $marshaller = $marshallerService->buildFieldefinitionMarshaller('block', $fd->getFieldtype());
                            // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                            $encodedData = $marshaller->marshal($normalizedData, ['object' => $object, 'fieldDefinition' => $fd, 'format' => 'block']);
                        }

                        // do not serialize the block element itself
                        $resultElement[$elementName] = [
                            'name' => $blockElement->getName(),
                            'type' => $blockElement->getType(),
                            'data' => $encodedData,
                        ];
                    }
                }
                $result[] = $resultElement;
            }
        }
        $result = Serialize::serialize($result);

        return $result;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data) {
            $count = 0;

            $unserializedData = Serialize::unserialize($data);
            $result = [];

            foreach ($unserializedData as $blockElements) {
                $items = [];
                foreach ($blockElements as $elementName => $blockElementRaw) {
                    $fd = $this->getFieldDefinition($elementName);
                    if (!$fd) {
                        // class definition seems to have changed
                        Logger::warn('class definition seems to have changed, element name: ' . $elementName);
                        continue;
                    }

                    // do not serialize the block element itself

                    $elementData = $blockElementRaw['data'];

                    if ($fd instanceof NormalizerInterface) {
                        /** @var MarshallerService $marshallerService */
                        $marshallerService = \Pimcore::getContainer()->get(MarshallerService::class);

                        if ($marshallerService->supportsFielddefinition('block', $fd->getFieldtype())) {
                            $unmarshaller = $marshallerService->buildFieldefinitionMarshaller('block', $fd->getFieldtype());
                            // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                            $elementData = $unmarshaller->unmarshal($elementData, ['object' => $object, 'fieldDefinition' => $fd, 'format' => 'block']);
                        }

                        $dataFromResource = $fd->denormalize($elementData, [
                            'object' => $object,
                            'fieldDefinition' => $fd,
                        ]);

                        $blockElementRaw['data'] = $dataFromResource;
                    }

                    $blockElement = new DataObject\Data\BlockElement($blockElementRaw['name'], $blockElementRaw['type'], $blockElementRaw['data']);

                    if ($blockElementRaw['type'] == 'localizedfields') {
                        /** @var DataObject\Localizedfield|null $data */
                        $data = $blockElementRaw['data'];
                        if ($data) {
                            $data->setObject($object);
                            $data->_setOwner($blockElement);
                            $data->_setOwnerFieldname('localizedfields');

                            $data->setContext(['containerType' => 'block',
                                'fieldname' => $this->getName(),
                                'index' => $count,
                                'containerKey' => $this->getName(),
                                'classId' => $object ? $object->getClassId() : null, ]);
                            $blockElementRaw['data'] = $data;
                        }
                    }

                    $blockElement->setNeedsRenewReferences(true);

                    $this->setBlockElementOwner($blockElement, $params);

                    $items[$elementName] = $blockElement;
                }
                $result[] = $items;
                $count++;
            }

            return $result;
        }

        return null;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $params = (array)$params;
        $result = [];
        $idx = -1;

        if (is_array($data)) {
            foreach ($data as $blockElements) {
                $resultElement = [];
                $idx++;

                /** @var DataObject\Data\BlockElement $blockElement */
                foreach ($blockElements as $elementName => $blockElement) {
                    $fd = $this->getFieldDefinition($elementName);
                    if (!$fd) {
                        // class definition seems to have changed
                        Logger::warn('class definition seems to have changed, element name: ' . $elementName);
                        continue;
                    }
                    $elementData = $blockElement->getData();
                    $params['context']['containerType'] = 'block';
                    $dataForEditMode = $fd->getDataForEditmode($elementData, $object, $params);
                    $resultElement[$elementName] = $dataForEditMode;
                }
                $result[] = [
                    'oIndex' => $idx,
                    'data' => $resultElement,
                ];
            }
        }

        return $result;
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
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $result = [];
        $count = 0;

        foreach ($data as $rawBlockElement) {
            $resultElement = [];

            $oIndex = $rawBlockElement['oIndex'] ?? null;
            $blockElement = $rawBlockElement['data'] ?? null;
            $blockElementDefinition = $this->getFieldDefinitions();

            foreach ($blockElementDefinition as $elementName => $fd) {
                $elementType = $fd->getFieldtype();
                $invisible = $fd->getInvisible();
                if ($invisible && !is_null($oIndex)) {
                    $blockGetter = 'get' . ucfirst($this->getname());
                    if (method_exists($object, $blockGetter)) {
                        $language = $params['language'] ?? null;
                        $items = $object->$blockGetter($language);
                        if (isset($items[$oIndex])) {
                            $item = $items[$oIndex][$elementName];
                            $blockData = $item->getData();
                            $resultElement[$elementName] = new DataObject\Data\BlockElement($elementName, $elementType, $blockData);
                        }
                    } else {
                        $params['blockGetter'] = $blockGetter;
                        $blockData = $this->getBlockDataFromContainer($object, $params);
                        if ($blockData) {
                            $resultElement = $blockData[$oIndex];
                        }
                    }
                } else {
                    $elementData = $blockElement[$elementName];
                    $blockData = $fd->getDataFromEditmode(
                        $elementData,
                        $object,
                        [
                            'context' => [
                                'containerType' => 'block',
                                'fieldname' => $this->getName(),
                                'index' => $count,
                                'oIndex' => $oIndex,
                                'classId' => $object->getClassId(),
                            ],
                        ]
                    );

                    $resultElement[$elementName] = new DataObject\Data\BlockElement($elementName, $elementType, $blockData);
                }
            }

            $result[] = $resultElement;
            $count++;
        }

        return $result;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getBlockDataFromContainer($object, $params = [])
    {
        $data = null;

        $context = $params['context'] ?? null;

        if (isset($context['containerType'])) {
            if ($context['containerType'] === 'fieldcollection') {
                $fieldname = $context['fieldname'];

                if ($object instanceof DataObject\Concrete) {
                    $containerGetter = 'get' . ucfirst($fieldname);
                    $container = $object->$containerGetter();
                    if ($container) {
                        $originalIndex = $context['oIndex'];

                        // field collection or block items
                        if (!is_null($originalIndex)) {
                            $items = $container->getItems();

                            if ($items && count($items) > $originalIndex) {
                                $item = $items[$originalIndex];

                                $getter = 'get' . ucfirst($this->getName());
                                $data = $item->$getter();

                                return $data;
                            }
                        } else {
                            return null;
                        }
                    } else {
                        return null;
                    }
                }
            } elseif ($context['containerType'] === 'objectbrick') {
                $fieldname = $context['fieldname'];

                if ($object instanceof DataObject\Concrete) {
                    $containerGetter = 'get' . ucfirst($fieldname);
                    $container = $object->$containerGetter();
                    if ($container) {
                        $brickGetter = 'get' . ucfirst($context['containerKey']);
                        /** @var DataObject\Objectbrick\Data\AbstractData|null $brickData */
                        $brickData = $container->$brickGetter();

                        if ($brickData) {
                            $blockGetter = $params['blockGetter'];
                            $data = $brickData->$blockGetter();

                            return $data;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param array|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return 'not supported';
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either HTML or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param array|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            return 'not supported';
        }

        return '';
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\Block $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->disallowAddRemove = $masterDefinition->disallowAddRemove;
        $this->disallowReorder = $masterDefinition->disallowReorder;
        $this->collapsible = $masterDefinition->collapsible;
        $this->collapsed = $masterDefinition->collapsed;
    }

    /**
     * @param DataObject\Data\BlockElement[][]|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return is_null($data) || count($data) === 0;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->childs;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->childs = $children;
        $this->fieldDefinitionsCache = null;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        if (is_array($this->childs) && count($this->childs) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param Data|Layout $child
     */
    public function addChild($child)
    {
        $this->childs[] = $child;
        $this->fieldDefinitionsCache = null;
    }

    /**
     * @param array|null $layout
     *
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param mixed $def
     * @param array $fields
     *
     * @return array
     */
    public function doGetFieldDefinitions($def = null, $fields = [])
    {
        if ($def === null) {
            $def = $this->getChildren();
        }

        if (is_array($def)) {
            foreach ($def as $child) {
                $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
            }
        }

        if ($def instanceof DataObject\ClassDefinition\Layout) {
            if ($def->hasChildren()) {
                foreach ($def->getChildren() as $child) {
                    $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
                }
            }
        }

        if ($def instanceof DataObject\ClassDefinition\Data) {
            $existing = $fields[$def->getName()] ?? false;
            if ($existing && method_exists($existing, 'addReferencedField')) {
                // this is especially for localized fields which get aggregated here into one field definition
                // in the case that there are more than one localized fields in the class definition
                // see also pimcore.object.edit.addToDataFields();
                $existing->addReferencedField($def);
            } else {
                $fields[$def->getName()] = $def;
            }
        }

        return $fields;
    }

    /**
     * @param array $context additional contextual data
     *
     * @return DataObject\ClassDefinition\Data[]
     */
    public function getFieldDefinitions($context = [])
    {
        if (empty($this->fieldDefinitionsCache)) {
            $definitions = $this->doGetFieldDefinitions();
            foreach ($this->getReferencedFields() as $rf) {
                if ($rf instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $definitions = array_merge($definitions, $this->doGetFieldDefinitions($rf->getChildren()));
                }
            }

            $this->fieldDefinitionsCache = $definitions;
        }

        if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
            return $this->fieldDefinitionsCache;
        }

        $enrichedFieldDefinitions = [];
        if (is_array($this->fieldDefinitionsCache)) {
            foreach ($this->fieldDefinitionsCache as $key => $fieldDefinition) {
                $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
                $enrichedFieldDefinitions[$key] = $fieldDefinition;
            }
        }

        return $enrichedFieldDefinitions;
    }

    /**
     * @param string $name
     * @param array $context additional contextual data
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    public function getFieldDefinition($name, $context = [])
    {
        $fds = $this->getFieldDefinitions();
        if (isset($fds[$name])) {
            if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
                return $fds[$name];
            }
            $fieldDefinition = $this->doEnrichFieldDefinition($fds[$name], $context);

            return $fieldDefinition;
        }

        return null;
    }

    protected function doEnrichFieldDefinition($fieldDefinition, $context = [])
    {
        if (method_exists($fieldDefinition, 'enrichFieldDefinition')) {
            $context['containerType'] = 'block';
            $context['containerKey'] = $this->getName();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @param array $referencedFields
     */
    public function setReferencedFields($referencedFields)
    {
        $this->referencedFields = $referencedFields;
    }

    /**
     * @return Data[]
     */
    public function getReferencedFields()
    {
        return $this->referencedFields;
    }

    /**
     * @param Data $field
     */
    public function addReferencedField($field)
    {
        $this->referencedFields[] = $field;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $vars = get_object_vars($this);
        $blockedVars = [
            'fieldDefinitionsCache',
            'referencedFields',
            'blockedVarsForExport',
        ];

        foreach ($blockedVars as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    /**
     * @param array|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (!is_array($data)) {
            return [];
        }

        foreach ($data as $blockElements) {
            foreach ($blockElements as $elementName => $blockElement) {
                $fd = $this->getFieldDefinition($elementName);
                if (!$fd) {
                    // class definition seems to have changed
                    Logger::warn('class definition seems to have changed, element name: ' . $elementName);
                    continue;
                }
                $elementData = $blockElement->getData();

                $dependencies = array_merge($dependencies, $fd->resolveDependencies($elementData));
            }
        }

        return $dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
    {
        if ($this->getLazyLoading()) {
            return $tags;
        }

        if (!is_array($data)) {
            return $tags;
        }

        foreach ($data as $blockElements) {
            foreach ($blockElements as $elementName => $blockElement) {
                $fd = $this->getFieldDefinition($elementName);
                if (!$fd) {
                    // class definition seems to have changed
                    Logger::warn('class definition seems to have changed, element name: ' . $elementName);
                    continue;
                }
                $data = $blockElement->getData();

                $tags = $fd->getCacheTags($data, $tags);
            }
        }

        return $tags;
    }

    /**
     * @return bool
     */
    public function isCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * @param bool $collapsed
     */
    public function setCollapsed($collapsed)
    {
        $this->collapsed = $collapsed;
    }

    /**
     * @return bool
     */
    public function isCollapsible()
    {
        return $this->collapsible;
    }

    /**
     * @param bool $collapsible
     */
    public function setCollapsible($collapsible)
    {
        $this->collapsible = $collapsible;
    }

    /**
     * @return string
     */
    public function getStyleElement()
    {
        return $this->styleElement;
    }

    /**
     * @param string $styleElement
     *
     * @return $this
     */
    public function setStyleElement($styleElement)
    {
        $this->styleElement = $styleElement;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLazyLoading()
    {
        return $this->lazyLoading;
    }

    /**
     * @param int|bool|null $lazyLoading
     *
     * @return $this
     */
    public function setLazyLoading($lazyLoading)
    {
        $this->lazyLoading = $lazyLoading;

        return $this;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array|null $data
     * @param array $params
     *
     * @return mixed
     */
    public function preSetData($object, $data, $params = [])
    {
        $this->markLazyloadedFieldAsLoaded($object);

        $lf = $this->getFieldDefinition('localizedfields');
        if ($lf && is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    foreach ($item as $itemElement) {
                        if ($itemElement->getType() === 'localizedfields') {
                            /** @var DataObject\Localizedfield $itemElementData */
                            $itemElementData = $itemElement->getData();
                            $itemElementData->setObject($object);

                            // the localized field needs at least the containerType as this is important
                            // for lazy loading
                            $context = $itemElementData->getContext() ? $itemElementData->getContext() : [];
                            $context['containerType'] = 'block';
                            $context['containerKey'] = $this->getName();
                            $itemElementData->setContext($context);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function save($object, $params = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function load($container, $params = [])
    {
        $field = $this->getName();
        $db = Db::get();
        $data = null;

        if ($container instanceof DataObject\Concrete) {
            $query = 'select ' . $db->quoteIdentifier($field) . ' from object_store_' . $container->getClassId() . ' where oo_id  = ' . $container->getId();
            $data = $db->fetchOne($query);
            $data = $this->getDataFromResource($data, $container, $params);
        } elseif ($container instanceof DataObject\Localizedfield) {
            $context = $params['context'];
            $object = $context['object'];
            $containerType = $context['containerType'] ?? null;

            if ($containerType === 'fieldcollection') {
                $query = 'select ' . $db->quoteIdentifier($field) . ' from object_collection_' . $context['containerKey'] . '_localized_' . $object->getClassId() . ' where language = ' . $db->quote($params['language']) . ' and  ooo_id  = ' . $object->getId() . ' and fieldname = ' . $db->quote($context['fieldname']) . ' and `index` =  ' . $context['index'];
            } elseif ($containerType === 'objectbrick') {
                $query = 'select ' . $db->quoteIdentifier($field) . ' from object_brick_localized_' . $context['containerKey'] . '_' . $object->getClassId() . ' where language = ' . $db->quote($params['language']) . ' and  ooo_id  = ' . $object->getId() . ' and fieldname = ' . $db->quote($context['fieldname']);
            } else {
                $query = 'select ' . $db->quoteIdentifier($field) . ' from object_localized_data_' . $object->getClassId() . ' where language = ' . $db->quote($params['language']) . ' and  ooo_id  = ' . $object->getId();
            }
            $data = $db->fetchOne($query);
            $data = $this->getDataFromResource($data, $container, $params);
        } elseif ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
            $context = $params['context'];

            $object = $context['object'];
            $brickType = $context['containerKey'];
            $brickField = $context['brickField'];
            $fieldname = $context['fieldname'];
            $query = 'select ' . $db->quoteIdentifier($brickField) . ' from object_brick_store_' . $brickType . '_' . $object->getClassId()
                . ' where  o_id  = ' . $object->getId() . ' and fieldname = ' . $db->quote($fieldname);
            $data = $db->fetchOne($query);
            $data = $this->getDataFromResource($data, $container, $params);
        } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $context = $params['context'];
            $collectionType = $context['containerKey'];
            $object = $context['object'];
            $fcField = $context['fieldname'];

            //TODO index!!!!!!!!!!!!!!

            $query = 'select ' . $db->quoteIdentifier($field) . ' from object_collection_' . $collectionType . '_' . $object->getClassId()
                . ' where  o_id  = ' . $object->getId() . ' and fieldname = ' . $db->quote($fcField) . ' and `index` = ' . $context['index'];
            $data = $db->fetchOne($query);
            $data = $this->getDataFromResource($data, $container, $params);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object, $params = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        $params['owner'] = $object;
        $params['fieldname'] = $this->getName();
        if ($object instanceof DataObject\Concrete) {
            $data = $object->getObjectVar($this->getName());
            if ($this->getLazyLoading() && !$object->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($object, $params);

                $setter = 'set' . ucfirst($this->getName());
                if (method_exists($object, $setter)) {
                    $object->$setter($data);
                    $this->markLazyloadedFieldAsLoaded($object);
                }
            }
        } elseif ($object instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $data = $object->getObjectVar($this->getName());
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $data = $object->getObjectVar($this->getName());
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * @param int $maxItems
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $maxItems;
    }

    /**
     * @return bool
     */
    public function isDisallowAddRemove()
    {
        return $this->disallowAddRemove;
    }

    /**
     * @param bool $disallowAddRemove
     */
    public function setDisallowAddRemove($disallowAddRemove)
    {
        $this->disallowAddRemove = $disallowAddRemove;
    }

    /**
     * @return bool
     */
    public function isDisallowReorder()
    {
        return $this->disallowReorder;
    }

    /**
     * @param bool $disallowReorder
     */
    public function setDisallowReorder($disallowReorder)
    {
        $this->disallowReorder = $disallowReorder;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck) {
            if (is_array($data)) {
                $blockDefinitions = $this->getFieldDefinitions();

                $validationExceptions = [];

                $idx = -1;
                foreach ($data as $item) {
                    $idx++;
                    if (!is_array($item)) {
                        continue;
                    }

                    foreach ($blockDefinitions as $fd) {
                        try {
                            $blockElement = $item[$fd->getName()] ?? null;
                            if (!$blockElement) {
                                if ($fd->getMandatory()) {
                                    throw new Element\ValidationException('Block element empty [ ' . $fd->getName() . ' ]');
                                } else {
                                    continue;
                                }
                            }

                            $data = $blockElement->getData();

                            if ($data instanceof DataObject\Localizedfield && $fd instanceof Localizedfields) {
                                foreach ($data->getInternalData() as $language => $fields) {
                                    foreach ($fields as $fieldName => $values) {
                                        $lfd = $fd->getFieldDefinition($fieldName);
                                        if ($lfd instanceof ManyToManyRelation || $lfd instanceof ManyToManyObjectRelation) {
                                            if (!method_exists($lfd, 'getAllowMultipleAssignments') || !$lfd->getAllowMultipleAssignments()) {
                                                $lfd->performMultipleAssignmentCheck($values);
                                            }
                                        }
                                    }
                                }
                            } elseif ($fd instanceof ManyToManyRelation || $fd instanceof ManyToManyObjectRelation) {
                                $fd->performMultipleAssignmentCheck($data);
                            }

                            $fd->checkValidity($data, false, $params);
                        } catch (Model\Element\ValidationException $ve) {
                            $ve->addContext($this->getName() . '-' . $idx);
                            $validationExceptions[] = $ve;
                        }
                    }
                }

                if ($validationExceptions) {
                    $aggregatedExceptions = new Model\Element\ValidationException();
                    $aggregatedExceptions->setSubItems($validationExceptions);
                    throw $aggregatedExceptions;
                }
            }
        }
    }

    /**
     * This method is called in DataObject\ClassDefinition::save()
     *
     * @param DataObject\ClassDefinition $class
     * @param array $params
     */
    public function classSaved($class, $params = [])
    {
        $blockDefinitions = $this->getFieldDefinitions();

        if (is_array($blockDefinitions)) {
            foreach ($blockDefinitions as $field) {
                if ($field instanceof LazyLoadingSupportInterface && $field->getLazyLoading()) {

                    // Lazy loading inside blocks isn't supported, turn it off if possible
                    if (method_exists($field, 'setLazyLoading')) {
                        $field->setLazyLoading(false);
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    private function setBlockElementOwner(DataObject\Data\BlockElement $blockElement, $params = [])
    {
        if (!isset($params['owner'])) {
            throw new \Error('owner missing');
        } else {
            // addition check. if owner is passed but no fieldname then there is something wrong with the params.
            if (!array_key_exists('fieldname', $params)) {
                // do not throw an exception because it is silently swallowed by the caller
                throw new \Error('params contains owner but no fieldname');
            }

            if ($params['owner'] instanceof DataObject\Localizedfield) {
                //make sure that for a localized field parent the language param is set and not empty
                if (($params['language'] ?? null) === null) {
                    throw new \Error('language param missing');
                }
            }
            $blockElement->_setOwner($params['owner']);
            $blockElement->_setOwnerFieldname($params['fieldname']);
            $blockElement->_setOwnerLanguage($params['language'] ?? null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\BlockElement::class . '[][]';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' .DataObject\Data\BlockElement::class . '[][]';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        $result = null;
        if ($value) {
            $result = [];
            $fieldDefinitions = $this->getFieldDefinitions();
            foreach ($value as $block) {
                $resultItem = [];
                /**
                 * @var  string $key
                 * @var  DataObject\Data\BlockElement $fieldValue
                 */
                foreach ($block as $key => $fieldValue) {
                    $fd = $fieldDefinitions[$key];

                    if ($fd instanceof NormalizerInterface) {
                        $normalizedData = $fd->normalize($fieldValue->getData(), [
                            'object' => $params['object'] ?? null,
                            'fieldDefinition' => $fd,
                        ]);
                        $resultItem[$key] = $normalizedData;
                    } else {
                        throw new \Exception('data type ' . $fd->getFieldtype() . ' does not implement normalizer interface');
                    }
                }
                $result[] = $resultItem;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            $fieldDefinitions = $this->getFieldDefinitions();

            foreach ($value as $idx => $blockItem) {
                $resultItem = [];
                /**
                 * @var  string $key
                 * @var  DataObject\Data\BlockElement $fieldValue
                 */
                foreach ($blockItem as $key => $fieldValue) {
                    $fd = $fieldDefinitions[$key];

                    if ($fd instanceof NormalizerInterface) {
                        $denormalizedData = $fd->denormalize($fieldValue, [
                            'object' => $params['object'],
                            'fieldDefinition' => $fd,
                        ]);
                        $resultItem[$key] = $denormalizedData;
                    } else {
                        throw new \Exception('data type does not implement normalizer interface');
                    }
                }
                $result[] = $resultItem;
            }

            return $result;
        }

        return null;
    }
}
