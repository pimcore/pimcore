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

use Exception;
use Pimcore;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Normalizer\NormalizerInterface;

class Fieldcollections extends Data implements CustomResourcePersistingInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface, NormalizerInterface, DataContainerAwareInterface, IdRewriterInterface, PreGetDataInterface, PreSetDataInterface
{
    use DataObject\Traits\ClassSavedTrait;

    /**
     * @internal
     *
     */
    public array $allowedTypes = [];

    /**
     * @internal
     */
    public bool $lazyLoading = false;

    /**
     * @internal
     *
     */
    public ?int $maxItems = null;

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
    public bool $collapsed = false;

    /**
     * @internal
     */
    public bool $collapsible = false;

    /**
     * @internal
     */
    public bool $border = false;

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

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $editmodeData = [];
        $idx = -1;

        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                $idx++;

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    $collectionData = [];

                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if (!$fd instanceof CalculatedValue) {
                            $value = $item->{'get' . $fd->getName()}();
                            $params['context']['containerKey'] = $idx;
                            $collectionData[$fd->getName()] = $fd->getDataForEditmode($value, $object, $params);
                        }
                    }

                    $calculatedChildren = [];
                    self::collectCalculatedValueItems($collectionDef->getFieldDefinitions(), $calculatedChildren);

                    if ($calculatedChildren) {
                        foreach ($calculatedChildren as $fd) {
                            $data = new DataObject\Data\CalculatedValue($fd->getName());
                            $data->setContextualData('fieldcollection', $this->getName(), $idx, null, null, null, $fd);
                            $data = $fd->getDataForEditmode($data, $object, $params);
                            $collectionData[$fd->getName()] = $data;
                        }
                    }

                    $editmodeData[] = [
                        'data' => $collectionData,
                        'type' => $item->getType(),
                        'oIndex' => $idx,
                        'title' => $collectionDef->getTitle(),
                    ];
                }
            }
        }

        return $editmodeData;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): DataObject\Fieldcollection
    {
        $values = [];
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {
                $collectionData = [];
                $collectionKey = $collectionRaw['type'];

                $oIndex = $collectionRaw['oIndex'] ?? null;

                $collectionDef = DataObject\Fieldcollection\Definition::getByKey($collectionKey);
                $fieldname = $this->getName();

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $invisible = $fd->getInvisible();
                    if ($invisible && !is_null($oIndex)) {
                        $containerGetter = 'get' . ucfirst($fieldname);
                        $container = $object->$containerGetter();
                        if ($container) {
                            $items = $container->getItems();
                            $invisibleData = null;
                            if ($items && count($items) > $oIndex) {
                                $item = $items[$oIndex];
                                $getter = 'get' . ucfirst($fd->getName());
                                $invisibleData = $item->$getter();
                            }

                            $collectionData[$fd->getName()] = $invisibleData;
                        }
                    } elseif (array_key_exists($fd->getName(), $collectionRaw['data'])) {
                        $collectionParams = [
                            'context' => [
                                'containerType' => 'fieldcollection',
                                'containerKey' => $collectionKey,
                                'fieldname' => $fieldname,
                                'index' => $count,
                                'oIndex' => $oIndex,
                            ],
                        ];

                        $collectionData[$fd->getName()] = $fd->getDataFromEditmode(
                            $collectionRaw['data'][$fd->getName()],
                            $object,
                            $collectionParams
                        );
                    }
                }

                $collectionClass = '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($collectionRaw['type']);
                /** @var DataObject\Fieldcollection\Data\AbstractData $collection */
                $collection = Pimcore::getContainer()->get('pimcore.model.factory')->build($collectionClass);
                $collection->setObject($object);
                $collection->setIndex($count);
                $collection->setFieldname($this->getName());
                $collection->setValues($collectionData);

                $values[] = $collection;

                $count++;
            }
        }

        $container = new DataObject\Fieldcollection($values, $this->getName());

        return $container;
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return $this->getDiffVersionPreview($data, $object, $params)['html'];
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return 'NOT SUPPORTED';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $dataString = '';
        $fcData = $this->getDataFromObjectParam($object);
        if ($fcData instanceof DataObject\Fieldcollection) {
            foreach ($fcData as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $dataString .= $fd->getDataForSearchIndex($item, $params) . ' ';
                    }
                }
            }
        }

        return $dataString;
    }

    public function save(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $container = $this->getDataFromObjectParam($object);

        if (is_null($container)) {
            $container = new DataObject\Fieldcollection();
            $container->setFieldname($this->getName());
        }

        if ($container instanceof DataObject\Fieldcollection) {
            $params = [
                'context' => [
                    'containerType' => 'fieldcollection',
                    'fieldname' => $this->getName(),
                ],
            ];

            $container->save($object, $params);
        } else {
            throw new Exception('Invalid value for field "' . $this->getName()."\" provided. You have to pass a DataObject\\Fieldcollection or 'null'");
        }
    }

    public function load(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): ?DataObject\Fieldcollection
    {
        $container = new DataObject\Fieldcollection([], $this->getName());
        $container->load($object);

        if ($container->isEmpty()) {
            return null;
        }

        return $container;
    }

    public function delete(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $container = new DataObject\Fieldcollection([], $this->getName());
        $container->delete($object);
    }

    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * @return $this
     */
    public function setAllowedTypes(array|string|null $allowedTypes): static
    {
        if (is_string($allowedTypes)) {
            $allowedTypes = explode(',', $allowedTypes);
        }

        if (is_array($allowedTypes)) {
            for ($i = 0; $i < count($allowedTypes); $i++) {
                if (!DataObject\Fieldcollection\Definition::getByKey($allowedTypes[$i])) {
                    Logger::warn("Removed unknown allowed type [ $allowedTypes[$i] ] from allowed types of field collection");
                    unset($allowedTypes[$i]);
                }
            }
        }

        $this->allowedTypes = (array)$allowedTypes;
        $this->allowedTypes = array_values($this->allowedTypes); // get rid of indexed array (.join() doesnt work in JS)

        return $this;
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $getter = 'get' . ucfirst($fd->getName());
                        $dependencies = array_merge($dependencies, $fd->resolveDependencies($item->$getter()));
                    }
                }
            }
        }

        return $dependencies;
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $getter = 'get' . ucfirst($fd->getName());
                        $tags = $fd->getCacheTags($item->$getter(), $tags);
                    }
                }
            }
        }

        return $tags;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if ($data instanceof DataObject\Fieldcollection) {
            $validationExceptions = [];

            $idx = -1;
            foreach ($data as $item) {
                $idx++;
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                //max limit check should be performed irrespective of omitMandatory check
                if (!empty($this->maxItems) && $idx + 1 > $this->maxItems) {
                    throw new Model\Element\ValidationException('Maximum limit reached for items in field collection: ' . $this->getName());
                }

                if (!$omitMandatoryCheck) {
                    if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                        foreach ($collectionDef->getFieldDefinitions() as $fd) {
                            try {
                                $getter = 'get' . ucfirst($fd->getName());
                                if (!$fd instanceof CalculatedValue) {
                                    $fd->checkValidity($item->$getter(), false, $params);
                                }
                            } catch (Model\Element\ValidationException $ve) {
                                $ve->addContext($this->getName() . '-' . $idx);
                                $validationExceptions[] = $ve;
                            }
                        }
                    }
                }
            }

            if ($validationExceptions) {
                $errors = [];
                /** @var Model\Element\ValidationException $e */
                foreach ($validationExceptions as $e) {
                    $errors[] = $e->getAggregatedMessage();
                }
                $message = implode(' / ', $errors);

                throw new Model\Element\ValidationException($message);
            }
        }
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        if (!$container instanceof DataObject\Concrete) {
            throw new Exception('Field Collections are only valid in Objects');
        }

        $data = $container->getObjectVar($this->getName());
        if ($this->getLazyLoading() && !$container->isLazyKeyLoaded($this->getName())) {
            $data = $this->load($container);
            if ($data instanceof Model\Element\DirtyIndicatorInterface) {
                $data->resetDirtyMap();
            }

            $setter = 'set' . ucfirst($this->getName());
            if (method_exists($container, $setter)) {
                $container->$setter($data);
                $this->markLazyloadedFieldAsLoaded($container);
            }
        }

        return $data;
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        $this->markLazyloadedFieldAsLoaded($container);

        if ($data instanceof DataObject\Fieldcollection) {
            $data->setFieldname($this->getName());
        }

        return $data;
    }

    public function getDataForGrid(?DataObject\Fieldcollection $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (null === $data) {
            return null;
        }

        $dataForGrid = [];

        foreach ($data as $item) {
            if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                continue;
            }

            $itemData = [];
            $collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType());
            if ($collectionDef instanceof DataObject\Fieldcollection\Definition) {
                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    if ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                        foreach ($fd->getFieldDefinitions() as $localizedFieldDefinition) {
                            $getter = 'get'.ucfirst($localizedFieldDefinition->getName());
                            $itemData[$localizedFieldDefinition->getName()] = [
                                'title' => $localizedFieldDefinition->getTitle(),
                                'value' => $localizedFieldDefinition->getVersionPreview($item->$getter(), $object, $params),
                            ];
                        }
                    } else {
                        $getter = 'get'.ucfirst($fd->getName());
                        $itemData[$fd->getName()] = [
                            'title' => $fd->getTitle(),
                            'value' => $fd->getVersionPreview($item->$getter(), $object, $params),
                        ];
                    }
                }
            }

            $dataForGrid[] = [
                'type' => $collectionDef->getKey(),
                'data' => $itemData,
            ];
        }

        return $dataForGrid;
    }

    public function getGetterCode(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
    {
        // getter, no inheritance here, that's the only difference
        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getReturnTypeDeclaration()) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . '()' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        $code .= $this->getPreGetValueHookCode($key);

        // TODO else part should not be needed at all as preGetData is always there
        // if ($this instanceof PreGetDataInterface) {
        $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        //        } else {
        //            $code .= "\t" . '$data = $this->' . $key . ";\n";
        //        }

        $code .= "\t" . 'return $data;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * @return $this
     */
    public function setMaxItems(?int $maxItems): static
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
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
    public function getDiffVersionPreview(?DataObject\Fieldcollection $data, Concrete $object = null, array $params = []): array
    {
        $html = '';
        if ($data instanceof DataObject\Fieldcollection) {
            $html = '<table>';
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                $type = $item->getType();
                $html .= '<tr><th><b>' . $type . '</b></th><th>&nbsp;</th><th>&nbsp;</th></tr>';

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $title = !empty($fd->title) ? $fd->title : $fd->getName();
                        $html .= '<tr><td>&nbsp;</td><td>' . $title . '</td><td>';
                        $html .= $fd->getVersionPreview($item->getObjectVar($fd->getName()), $object, $params);
                        $html .= '</td></tr>';
                    }
                }
            }

            $html .= '</table>';
        }

        $value = [];
        $value['html'] = $html;
        $value['type'] = 'html';

        return $value;
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);

        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if ($fd instanceof IdRewriterInterface
                            && $fd instanceof DataObject\ClassDefinition\Data) {
                            $d = $fd->rewriteIds($item, $idMapping, $params);
                            $setter = 'set' . ucfirst($fd->getName());
                            $item->$setter($d);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Fieldcollections $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->allowedTypes = $mainDefinition->allowedTypes;
        $this->lazyLoading = $mainDefinition->lazyLoading;
        $this->maxItems = $mainDefinition->maxItems;
    }

    /**
     * This method is called in DataObject\ClassDefinition::save() and is used to create the database table for the localized data
     *
     */
    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        foreach ($this->allowedTypes as $i => $allowedType) {
            if ($definition = DataObject\Fieldcollection\Definition::getByKey($allowedType)) {
                $definition->getDao()->createUpdateTable($class);
                $fieldDefinition = $definition->getFieldDefinitions();

                foreach ($fieldDefinition as $fd) {
                    if ($fd instanceof ClassSavedInterface) {
                        // defer creation
                        $fd->classSaved($class, $params);
                    }
                }

                $definition->getDao()->classSaved($class);
            } else {
                Logger::warn("Removed unknown allowed type [ $allowedType ] from allowed types of field collection");
                unset($this->allowedTypes[$i]);
            }
        }
    }

    public function setDisallowAddRemove(bool $disallowAddRemove): void
    {
        $this->disallowAddRemove = $disallowAddRemove;
    }

    public function getDisallowAddRemove(): bool
    {
        return $this->disallowAddRemove;
    }

    public function setDisallowReorder(bool $disallowReorder): void
    {
        $this->disallowReorder = $disallowReorder;
    }

    public function getDisallowReorder(): bool
    {
        return $this->disallowReorder;
    }

    public function getBorder(): bool
    {
        return $this->border;
    }

    public function setBorder(bool $border): void
    {
        $this->border = $border;
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

    /**
     * @param DataObject\ClassDefinition\Data[] $container
     * @param DataObject\ClassDefinition\Data[] $list
     */
    public static function collectCalculatedValueItems(array $container, array &$list = []): void
    {
        foreach ($container as $childDef) {
            if ($childDef instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
                $list[] = $childDef;
            } else {
                if (method_exists($childDef, 'getFieldDefinitions')) {
                    self::collectCalculatedValueItems($childDef->getFieldDefinitions(), $list);
                }
            }
        }
    }

    public function supportsInheritance(): bool
    {
        return false;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Fieldcollection::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Fieldcollection::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Fieldcollection::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Fieldcollection::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Fieldcollection) {
            $resultItems = [];
            $items = $value->getItems();

            /** @var DataObject\Fieldcollection\Data\AbstractData $item */
            foreach ($items as $item) {
                $type = $item->getType();

                $resultItem = ['type' => $type];

                $fcDef = DataObject\Fieldcollection\Definition::getByKey($type);
                $fcs = $fcDef->getFieldDefinitions();
                foreach ($fcs as $fc) {
                    $getter = 'get' . ucfirst($fc->getName());
                    $value = $item->$getter();

                    if ($fc instanceof NormalizerInterface) {
                        $value = $fc->normalize($value, $params);
                    }
                    $resultItem[$fc->getName()] = $value;
                }

                $resultItems[] = $resultItem;
            }

            return $resultItems;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Fieldcollection
    {
        if (is_array($value)) {
            $resultItems = [];
            foreach ($value as $idx => $itemData) {
                $type = $itemData['type'];
                $fcDef = DataObject\Fieldcollection\Definition::getByKey($type);

                $collectionClass = '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($type);
                /** @var DataObject\Fieldcollection\Data\AbstractData $collection */
                $collection = Pimcore::getContainer()->get('pimcore.model.factory')->build($collectionClass);
                $collection->setObject($params['object'] ?? null);
                $collection->setIndex($idx);
                $collection->setFieldname($params['fieldname'] ?? null);

                foreach ($itemData as $fieldKey => $fieldValue) {
                    if ($fieldKey == 'type') {
                        continue;
                    }
                    $fc = $fcDef->getFieldDefinition($fieldKey);
                    if ($fc instanceof NormalizerInterface) {
                        $fieldValue = $fc->denormalize($fieldValue, $params);
                    }
                    $collection->set($fieldKey, $fieldValue);
                }
                $resultItems[] = $collection;
            }

            $resultCollection = new DataObject\Fieldcollection();
            $resultCollection->setItems($resultItems);

            return $resultCollection;
        }

        return null;
    }

    public function getFieldType(): string
    {
        return 'fieldcollections';
    }
}
