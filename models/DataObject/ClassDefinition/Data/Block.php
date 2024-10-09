<?php
declare(strict_types=1);

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

use Error;
use Exception;
use Pimcore;
use Pimcore\Db;
use Pimcore\Element\MarshallerService;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Block extends Data implements CustomResourcePersistingInterface, ResourcePersistenceAwareInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface, VarExporterInterface, NormalizerInterface, DataContainerAwareInterface, PreGetDataInterface, PreSetDataInterface, FieldDefinitionEnrichmentModelInterface
{
    use DataObject\Traits\ClassSavedTrait;
    use DataObject\Traits\FieldDefinitionEnrichmentDataTrait;

    /**
     * @internal
     */
    public bool $lazyLoading = false;

    /**
     * @internal
     */
    public bool $disallowAddRemove = false;

    /**
     * @internal
     */
    public bool $disallowReorder = false;

    /**
     * @internal
     */
    public bool $collapsible = false;

    /**
     * @internal
     */
    public bool $collapsed = false;

    /**
     * @internal
     *
     */
    public ?int $maxItems = null;

    /**
     * @internal
     *
     */
    public string $styleElement = '';

    /**
     * @internal
     *
     */
    public array $children = [];

    /**
     * @internal
     *
     */
    public ?array $layout = null;

    /**
     * contains further child field definitions if there are more than one localized fields in on class
     *
     * @internal
     *
     */
    protected array $referencedFields = [];

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): string
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
                        $marshallerService = Pimcore::getContainer()->get(MarshallerService::class);

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
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $count = 0;

            //Fix old serialized data protected properties with \0*\0 prefix
            //https://github.com/pimcore/pimcore/issues/9973
            if (str_contains($data, ':" * ')) {
                $data = preg_replace_callback('!s:(\d+):" \* (.*?)";!', function ($match) {
                    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) .   ':"' . $match[2] . '";';
                }, $data);
            }

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
                        $marshallerService = Pimcore::getContainer()->get(MarshallerService::class);

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
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {

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

                    if (isset($params['owner'])) {
                        $this->setBlockElementOwner($blockElement, $params);
                    }
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
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $result = [];
        $count = 0;
        $context = $params['context'] ?? [];

        foreach ($data as $rawBlockElement) {
            $resultElement = [];

            $oIndex = $rawBlockElement['oIndex'] ?? null;
            $blockElement = $rawBlockElement['data'] ?? null;
            $blockElementDefinition = $this->getFieldDefinitions();

            foreach ($blockElementDefinition as $elementName => $fd) {
                $elementType = $fd->getFieldtype();
                $invisible = $fd->getInvisible();
                if ((!array_key_exists($elementName, $blockElement) || $invisible) && !is_null($oIndex)) {
                    $blockGetter = 'get' . ucfirst($this->getname());
                    if (empty($context['containerType']) && method_exists($object, $blockGetter)) {
                        $language = $params['language'] ?? null;
                        $items = $object->$blockGetter($language);
                        if (isset($items[$oIndex][$elementName])) {
                            $item = $items[$oIndex][$elementName];
                            $blockData = $blockElement[$elementName] ?? $item->getData();
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
                    $elementData = $blockElement[$elementName] ?? null;
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
     *
     * @throws Exception
     */
    protected function getBlockDataFromContainer(Concrete $object, array $params = []): mixed
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
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return $this->getDiffVersionPreview($data, $object, $params)['html'];
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either HTML or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDiffVersionPreview(?array $data, Concrete $object = null, array $params = []): array
    {
        $html = '';
        if (is_array($data)) {
            $html = '<table>';

            foreach ($data as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $html .= '<tr><th><b>'.$index.'</b></th><th>&nbsp;</th><th>&nbsp;</th></tr>';

                foreach ($this->getFieldDefinitions() as $fieldDefinition) {
                    $title = !empty($fieldDefinition->title) ? $fieldDefinition->title : $fieldDefinition->getName();
                    $html .= '<tr><td>&nbsp;</td><td>'.$title.'</td><td>';

                    $blockElement = $item[$fieldDefinition->getName()];
                    if ($blockElement instanceof DataObject\Data\BlockElement) {
                        $html .= $fieldDefinition->getVersionPreview($blockElement->getData(), $object, $params);
                    } else {
                        $html .= 'invalid data';
                    }
                    $html .= '</td></tr>';
                }
            }

            $html .= '</table>';
        }

        $value = [];
        $value['html'] = $html;
        $value['type'] = 'html';

        return $value;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\Block $mainDefinition
     */
    public function synchronizeWithMainDefinition(Model\DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->disallowAddRemove = $mainDefinition->disallowAddRemove;
        $this->disallowReorder = $mainDefinition->disallowReorder;
        $this->collapsible = $mainDefinition->collapsible;
        $this->collapsed = $mainDefinition->collapsed;
    }

    public function isEmpty(mixed $data): bool
    {
        return is_null($data) || count($data) === 0;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return $this
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;
        $this->setFieldDefinitions(null);

        return $this;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * typehint "mixed" is required for asset-metadata-definitions bundle
     * since it doesn't extend Core Data Types
     *
     * @param Data|Layout $child
     */
    public function addChild(mixed $child): void
    {
        $this->children[] = $child;
        $this->setFieldDefinitions(null);
    }

    /**
     * @return $this
     */
    public function setLayout(?array $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): ?array
    {
        return $this->layout;
    }

    /**
     * @internal
     */
    public function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data
    {
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
            $context['containerType'] = 'block';
            $context['containerKey'] = $this->getName();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    public function setReferencedFields(array $referencedFields): void
    {
        $this->referencedFields = $referencedFields;
        $this->setFieldDefinitions(null);
    }

    /**
     * @return Data[]
     */
    public function getReferencedFields(): array
    {
        return $this->referencedFields;
    }

    public function addReferencedField(Data $field): void
    {
        $this->referencedFields[] = $field;
        $this->setFieldDefinitions(null);
    }

    public function getBlockedVarsForExport(): array
    {
        return [
            'fieldDefinitionsCache',
            'referencedFields',
            'blockedVarsForExport',
            'childs',         //TODO remove in Pimcore 12
        ];
    }

    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        $blockedVars = $this->getBlockedVarsForExport();

        foreach ($blockedVars as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    public function resolveDependencies(mixed $data): array
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

    public function getCacheTags(mixed $data, array $tags = []): array
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

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): void
    {
        $this->collapsed = $collapsed;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function setCollapsible(bool $collapsible): void
    {
        $this->collapsible = $collapsible;
    }

    public function getStyleElement(): string
    {
        return $this->styleElement;
    }

    /**
     * @return $this
     */
    public function setStyleElement(string $styleElement): static
    {
        $this->styleElement = $styleElement;

        return $this;
    }

    public function getLazyLoading(): bool
    {
        return $this->lazyLoading;
    }

    /**
     * @return $this
     */
    public function setLazyLoading(bool $lazyLoading): static
    {
        $this->lazyLoading = $lazyLoading;

        return $this;
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        $this->markLazyloadedFieldAsLoaded($container);

        $lf = $this->getFieldDefinition('localizedfields');
        if ($lf && is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    foreach ($item as $itemElement) {
                        if ($itemElement->getType() === 'localizedfields') {
                            /** @var DataObject\Localizedfield $itemElementData */
                            $itemElementData = $itemElement->getData();
                            $itemElementData->setObject($container);

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

    public function save(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
    }

    public function load(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): mixed
    {
        $field = $this->getName();
        $db = Db::get();
        $data = null;

        if ($object instanceof DataObject\Concrete) {
            $query = 'select ' . $db->quoteIdentifier($field) . ' from object_store_' . $object->getClassId() . ' where oo_id  = ' . $object->getId();
            $data = $db->fetchOne($query);
            $data = $this->getDataFromResource($data, $object, $params);
        } elseif ($object instanceof DataObject\Localizedfield) {
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
            $data = $this->getDataFromResource($data, $object, $params);
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $context = $params['context'];

            $object = $context['object'];
            $brickType = $context['containerKey'];
            $brickField = $context['brickField'];
            $fieldname = $context['fieldname'];
            $query = 'select ' . $db->quoteIdentifier($brickField) . ' from object_brick_store_' . $brickType . '_' . $object->getClassId()
                . ' where  id  = ' . $object->getId() . ' and fieldname = ' . $db->quote($fieldname);
            $data = $db->fetchOne($query);
            $data = $this->getDataFromResource($data, $object, $params);
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $context = $params['context'];
            $collectionType = $context['containerKey'];
            $object = $context['object'];
            $fcField = $context['fieldname'];

            //TODO index!!!!!!!!!!!!!!

            $query = 'select ' . $db->quoteIdentifier($field) . ' from object_collection_' . $collectionType . '_' . $object->getClassId()
                . ' where  id  = ' . $object->getId() . ' and fieldname = ' . $db->quote($fcField) . ' and `index` = ' . $context['index'];
            $data = $db->fetchOne($query);
            $data = $this->getDataFromResource($data, $object, $params);
        }

        return $data;
    }

    public function delete(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        $data = null;
        $params['owner'] = $container;
        $params['fieldname'] = $this->getName();
        if ($container instanceof DataObject\Concrete) {
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
            $params['context'] = [
                'object' => $params['owner']->getObject(),
                'brickField' => $params['fieldname'],
                'containerKey' => $params['owner']->getType(),
                'fieldname' => $params['owner']->getFieldname(),
            ];
            $data = $container->getObjectVar($this->getName());
        }

        if ($this->getLazyLoading() && !$container->isLazyKeyLoaded($this->getName())) {
            $data = $this->load($container, $params);

            $setter = 'set' . ucfirst($this->getName());
            if (method_exists($container, $setter)) {
                $container->$setter($data);
            }
        }
        $this->preSetData($container, $data, $params);

        return is_array($data) ? $data : [];
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function setMaxItems(?int $maxItems): void
    {
        $this->maxItems = $maxItems;
    }

    public function isDisallowAddRemove(): bool
    {
        return $this->disallowAddRemove;
    }

    public function setDisallowAddRemove(bool $disallowAddRemove): void
    {
        $this->disallowAddRemove = $disallowAddRemove;
    }

    public function isDisallowReorder(): bool
    {
        return $this->disallowReorder;
    }

    public function setDisallowReorder(bool $disallowReorder): void
    {
        $this->disallowReorder = $disallowReorder;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
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

                            if ($fd instanceof Link) {
                                $params['resetInvalidFields'] = true;
                            }
                            $fd->checkValidity($data, false, $params);
                        } catch (Model\Element\ValidationException $ve) {
                            $ve->addContext($this->getName() . '-' . $idx);
                            $validationExceptions[] = $ve;
                        }
                    }
                }

                if ($validationExceptions) {
                    $errors = [];
                    /** @var Element\ValidationException $e */
                    foreach ($validationExceptions as $e) {
                        $errors[] = $e->getAggregatedMessage();
                    }
                    $message = implode(' / ', $errors);

                    throw new Model\Element\ValidationException($message);
                }
            }
        }
    }

    /**
     * This method is called in DataObject\ClassDefinition::save()
     *
     */
    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        $blockDefinitions = $this->getFieldDefinitions();

        foreach ($blockDefinitions as $field) {
            if ($field instanceof LazyLoadingSupportInterface && $field->getLazyLoading()) {
                // Lazy loading inside blocks isn't supported, turn it off if possible
                if (method_exists($field, 'setLazyLoading')) {
                    $field->setLazyLoading(false);
                }
            }
        }
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    private function setBlockElementOwner(DataObject\Data\BlockElement $blockElement, array $params = []): void
    {
        if (!isset($params['owner'])) {
            throw new Error('owner missing');
        } else {
            // addition check. if owner is passed but no fieldname then there is something wrong with the params.
            if (!array_key_exists('fieldname', $params)) {
                // do not throw an exception because it is silently swallowed by the caller
                throw new Error('params contains owner but no fieldname');
            }

            if ($params['owner'] instanceof DataObject\Localizedfield) {
                //make sure that for a localized field parent the language param is set and not empty
                if (($params['language'] ?? null) === null) {
                    throw new Error('language param missing');
                }
            }
            $blockElement->_setOwner($params['owner']);
            $blockElement->_setOwnerFieldname($params['fieldname']);
            $blockElement->_setOwnerLanguage($params['language'] ?? null);
        }
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\BlockElement::class . '[][]';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' .DataObject\Data\BlockElement::class . '[][]';
    }

    public function normalize(mixed $value, array $params = []): ?array
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
                        throw new Exception('data type ' . $fd->getFieldtype() . ' does not implement normalizer interface');
                    }
                }
                $result[] = $resultItem;
            }
        }

        return $result;
    }

    public function denormalize(mixed $value, array $params = []): ?array
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
                        throw new Exception('data type does not implement normalizer interface');
                    }
                }
                $result[] = $resultItem;
            }

            return $result;
        }

        return null;
    }

    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }

    public function getColumnType(): string
    {
        return 'longtext';
    }

    public function getFieldType(): string
    {
        return 'block';
    }
}
