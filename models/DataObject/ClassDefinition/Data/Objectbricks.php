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
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool;
use stdClass;

class Objectbricks extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface, NormalizerInterface, DataContainerAwareInterface, IdRewriterInterface, PreSetDataInterface
{
    use DataObject\Traits\ClassSavedTrait;

    /**
     * @internal
     *
     */
    public array $allowedTypes = [];

    /**
     * @internal
     *
     */
    public ?int $maxItems = null;

    /**
     * @internal
     */
    public bool $border = false;

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

    public function getBorder(): bool
    {
        return $this->border;
    }

    public function setBorder(bool $border): void
    {
        $this->border = $border;
    }

    /**
     * @see Data::getDataForEditmode
     *
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $editmodeData = [];

        if ($data instanceof DataObject\Objectbrick) {
            $allowedBrickTypes = $data->getAllowedBrickTypes();

            foreach ($allowedBrickTypes as $allowedBrickType) {
                $getter = 'get' . ucfirst($allowedBrickType);
                $params = [
                    'objectFromVersion' => $params['objectFromVersion'],
                    'context' => [
                        'containerType' => 'objectbrick',
                        'containerKey' => $allowedBrickType,
                    ],
                    'owner' => $data,
                    'fieldname' => $this->getName(),
                ];

                $editmodeData[] = $this->doGetDataForEditmode($getter, $data, $params, $allowedBrickType);
            }
        }

        return $editmodeData;
    }

    private function doGetDataForEditmode(string $getter, Objectbrick $data, ?array $params, string $allowedBrickType, int $level = 0): ?array
    {
        $object = $data->getObject();
        if ($object) {
            $parent = DataObject\Service::hasInheritableParentObject($object);
        }

        if (!method_exists($data, $getter)) {
            return null;
        }

        $item = $data->$getter();
        if (!$item && !empty($parent)) {
            $data = $parent->{'get' . ucfirst($this->getName())}();

            return $this->doGetDataForEditmode($getter, $data, $params, $allowedBrickType, $level + 1);
        }

        if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
            return null;
        }

        if (!$collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
            return null;
        }

        $brickData = [];
        $brickMetaData = [];

        $inherited = false;
        foreach ($collectionDef->getFieldDefinitions() as $fd) {
            if (!$fd instanceof CalculatedValue) {
                $fieldData = $this->getDataForField($item, $fd->getName(), $fd, $level, $data->getObject(), $getter, $params);
                $brickData[$fd->getName()] = $fieldData->objectData;
                $brickMetaData[$fd->getName()] = $fieldData->metaData;
                if ($fieldData->metaData['inherited'] == true) {
                    $inherited = true;
                }
            }
        }

        $calculatedChildren = [];
        self::collectCalculatedValueItems($collectionDef->getFieldDefinitions(), $calculatedChildren);

        foreach ($calculatedChildren as $fd) {
            $fieldData = new DataObject\Data\CalculatedValue($fd->getName());
            $fieldData->setContextualData('objectbrick', $this->getName(), $allowedBrickType, $fd->getName(), null, null, $fd);
            $fieldData = $fd->getDataForEditmode($fieldData, $data->getObject(), $params);
            $brickData[$fd->getName()] = $fieldData;
        }

        $brickDefinition = DataObject\Objectbrick\Definition::getByKey($allowedBrickType);

        $editmodeDataItem = [
            'data' => $brickData,
            'type' => $item->getType(),
            'metaData' => $brickMetaData,
            'inherited' => $inherited,
            'title' => $brickDefinition->getTitle(),
        ];

        return $editmodeDataItem;
    }

    /**
     * gets recursively attribute data from parent and fills objectData and metaData
     */
    private function getDataForField(Objectbrick\Data\AbstractData $item, string $key, Data $fielddefinition, int $level, ?DataObject\Concrete $baseObject, string $getter, ?array $params): stdClass
    {
        $result = new stdClass();
        if ($baseObject) {
            $parent = DataObject\Service::hasInheritableParentObject($baseObject);
        }
        $valueGetter = 'get' . ucfirst($key);

        // Editmode optimization for lazy loaded relations except metadata relations because they have additional data
        // which cannot be loaded directly from the DB.
        // Note that this is just for AbstractRelations, not for all LazyLoadingSupportInterface types.
        // It tries to optimize fetching the data needed for the editmode without loading the entire target element.

        if (
            (!isset($params['objectFromVersion']) || !$params['objectFromVersion'])
            && ($fielddefinition instanceof Data\Relations\AbstractRelations)
            && $fielddefinition->getLazyLoading()
            && !$fielddefinition instanceof ManyToManyObjectRelation
            && !$fielddefinition instanceof AdvancedManyToManyRelation
        ) {
            //lazy loading data is fetched from DB differently, so that not every relation object is instantiated
            $refKey = $key;
            $refId = null;

            $relations = $item->getRelationData($refKey, true, $refId);
            if (empty($relations) && !empty($parent)) {
                $parentItem = $parent->{'get' . ucfirst($this->getName())}();
                if (!empty($parentItem)) {
                    $parentItem = $parentItem->$getter();
                    if ($parentItem) {
                        return $this->getDataForField($parentItem, $key, $fielddefinition, $level + 1, $parent, $getter, $params);
                    }
                }
            }
            $data = [];

            if ($fielddefinition instanceof ManyToOneRelation) {
                $data = $relations[0] ?? null;
            } else {
                foreach ($relations as $rel) {
                    $data[] = ['id' => $rel['id'], 'fullpath' => $rel['path'],  'type' => $rel['type'], 'subtype' => $rel['subtype'], 'published' => ($rel['published'] ? true : false)];
                }
            }
            $result->objectData = $data;
            $result->metaData['objectid'] = $baseObject->getId();
            $result->metaData['inherited'] = $level != 0;
        } else {
            $fieldValue = $item->$valueGetter();
            $editmodeValue = $fielddefinition->getDataForEditmode($fieldValue, $baseObject, $params);

            if ($fielddefinition->isEmpty($fieldValue) && !empty($parent)) {
                $parentItem = DataObject\Service::useInheritedValues(true,
                    fn () => $parent->{'get' . ucfirst($this->getName())}()->$getter()
                );

                if (!empty($parentItem)) {
                    return $this->getDataForField($parentItem, $key, $fielddefinition, $level + 1, $parent, $getter, $params);
                }
            }
            $result->objectData = $editmodeValue;
            $result->metaData['objectid'] = $baseObject ? $baseObject->getId() : null;
            $result->metaData['inherited'] = $level != 0;
        }

        return $result;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): Objectbrick
    {
        $container = $this->getDataFromObjectParam($object);

        if (empty($container)) {
            $className = $object->getClass()->getName();

            $containerClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\' . ucfirst($this->getName());
            $container = new $containerClass($object, $this->getName());
        }

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {
                $collectionData = [];
                $collectionDef = DataObject\Objectbrick\Definition::getByKey($collectionRaw['type']);

                $getter = 'get' . ucfirst($collectionRaw['type']);
                $brick = $container->$getter();
                if (empty($brick)) {
                    $brickClass = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($collectionRaw['type']);
                    $brick = new $brickClass($object);
                }

                $brick->setFieldname($this->getName());

                if ($collectionRaw['data'] == 'deleted') {
                    $brick->setDoDelete(true);
                } else {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if (array_key_exists($fd->getName(), $collectionRaw['data'])) {
                            $collectionData[$fd->getName()] =
                                $fd->getDataFromEditmode($collectionRaw['data'][$fd->getName()], $object,
                                    [
                                        'context' => [
                                            'containerType' => 'objectbrick',
                                            'containerKey' => $collectionRaw['type'],
                                            'fieldname' => $this->getName(),
                                        ],
                                    ]);
                        }
                    }
                    $brick->setValues($collectionData);

                    $setter = 'set' . ucfirst($collectionRaw['type']);
                    $container->$setter($brick);
                }
            }
        }

        return $container;
    }

    /**
     * @see Data::getVersionPreview
     *
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        // this is handled directly in the template
        // https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/templates/admin/data_object/data_object/preview_version.html.twig
        return 'BRICKS';
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return 'NOT SUPPORTED';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $dataString = '';
        $obData = $this->getDataFromObjectParam($object, $params);

        if ($obData instanceof DataObject\Objectbrick) {
            $items = $obData->getItems();
            foreach ($items as $item) {
                if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
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
        if ($container instanceof DataObject\Objectbrick) {
            $container->save($object, $params);
        }
    }

    public function load(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): mixed
    {
        $classname = '\\Pimcore\\Model\\DataObject\\' . ucfirst($object->getClass()->getName()) . '\\' . ucfirst($this->getName());

        if (Tool::classExists($classname)) {
            $container = new $classname($object, $this->getName());
            $container->load($object);

            return $container;
        }

        return null;
    }

    public function delete(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $container = $this->load($object);
        if ($container) {
            $container->delete($object);
        }
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
                if (!DataObject\Objectbrick\Definition::getByKey($allowedTypes[$i])) {
                    Logger::warn("Removed unknown allowed type [ $allowedTypes[$i] ] from allowed types of object brick");
                    unset($allowedTypes[$i]);
                }
            }
        }

        $this->allowedTypes = (array)$allowedTypes;
        $this->allowedTypes = array_values($this->allowedTypes); // get rid of indexed array (.join() doesnt work in JS)

        return $this;
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        if ($data instanceof DataObject\Objectbrick) {
            $data->setFieldname($this->getName());
        }

        return $data;
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data instanceof DataObject\Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {
                if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $key = $fd->getName();
                        $getter = 'get' . ucfirst($key);
                        $dependencies = array_merge($dependencies, $fd->resolveDependencies($item->$getter()));
                    }
                }
            }
        }

        return $dependencies;
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if ($data instanceof DataObject\Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {
                if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions(['suppressEnrichment' => true]) as $fd) {
                        $key = $fd->getName();
                        $getter = 'get' . ucfirst($key);
                        $tags = $fd->getCacheTags($item->$getter(), $tags);
                    }
                }
            }
        }

        return $tags;
    }

    public function getGetterCode(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
    {
        // getter

        if ($this->getReturnTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $key = $this->getName();

        $classname = '\\Pimcore\\Model\\DataObject\\' . ucfirst($class->getName()) . '\\' . ucfirst($this->getName());

        $code = '/**' . "\n";
        $code .= '* @return ' . $classname . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . '()' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        $code .= "\t" . '$data = $this->' . $key . ";\n";
        $code .= "\t" . 'if (!$data) {' . "\n";

        $code .= "\t\t" . 'if (\Pimcore\Tool::classExists("' . str_replace('\\', '\\\\', $classname) . '")) {' . "\n";
        $code .= "\t\t\t" . '$data = new ' . $classname . '($this, "' . $key . '");' . "\n";
        $code .= "\t\t\t" . '$this->' . $key . ' = $data;' . "\n";
        $code .= "\t\t" . '} else {' . "\n";
        $code .= "\t\t\t" . 'return null;' . "\n";
        $code .= "\t\t" . '}' . "\n";
        $code .= "\t" . '}' . "\n";

        if ($this instanceof PreGetDataInterface) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        }

        $code .= $this->getPreGetValueHookCode($key);

        $code .= "\t" . 'return $data;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if ($data instanceof DataObject\Objectbrick) {
            $validationExceptions = [];

            $itemCount = 0;
            $allowedTypes = $this->getAllowedTypes();
            foreach ($allowedTypes as $allowedType) {
                $getter = 'get' . ucfirst($allowedType);
                /** @var DataObject\Objectbrick\Data\AbstractData $item */
                $item = $data->$getter();

                if ($item instanceof DataObject\Objectbrick\Data\AbstractData) {
                    if ($item->getDoDelete()) {
                        continue;
                    }

                    $itemCount++;

                    if (!$collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
                        continue;
                    }

                    //max limit check should be performed irrespective of omitMandatory check
                    if (!empty($this->maxItems) && $itemCount > $this->maxItems) {
                        throw new Model\Element\ValidationException('Maximum limit reached for items in brick: ' . $this->getName());
                    }

                    //needed when new brick is added but not saved yet - then validity check fails.
                    if (!$item->getFieldname()) {
                        $item->setFieldname($data->getFieldname());
                    }

                    if (!$omitMandatoryCheck) {
                        foreach ($collectionDef->getFieldDefinitions() as $fd) {
                            $key = $fd->getName();
                            $getter = 'get' . ucfirst($key);

                            try {
                                $fd->checkValidity($item->$getter(), false, $params);
                            } catch (Model\Element\ValidationException $ve) {
                                if ($item->getObject()->getClass()->getAllowInherit() && $fd->supportsInheritance() && $fd->isEmpty($item->$getter())) {
                                    //try again with parent data when inheritance is activated
                                    try {
                                        $getInheritedValues = DataObject::doGetInheritedValues();
                                        DataObject::setGetInheritedValues(true);

                                        $fd->checkValidity($item->$getter(), $omitMandatoryCheck, $params);

                                        DataObject::setGetInheritedValues($getInheritedValues);
                                    } catch (Exception $e) {
                                        if (!$e instanceof Model\Element\ValidationException) {
                                            throw $e;
                                        }
                                        $e->addContext($this->getName());
                                        $validationExceptions[] = $e;
                                    }
                                } else {
                                    $ve->addContext($this->getName());
                                    $validationExceptions[] = $ve;
                                }
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

                throw new Model\Element\ValidationException('invalid brick ' . $this->getName().': '.$message);
            }
        }
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataForGrid(?Objectbrick $data, Concrete $object = null, array $params = []): string
    {
        return 'NOT SUPPORTED';
    }

    private function getDiffDataForField(Objectbrick\Data\AbstractData $item, string $key, Data $fielddefinition, int $level, DataObject\Concrete $baseObject, string $getter, array $params = []): ?array
    {
        $valueGetter = 'get' . ucfirst($key);

        $value = $fielddefinition->getDiffDataForEditmode($item->$valueGetter(), $baseObject, $params);

        return $value;
    }

    private function doGetDiffDataForEditmode(Objectbrick $data, string $getter, array $params = [], int $level = 0): ?array
    {
        $parent = DataObject\Service::hasInheritableParentObject($data->getObject());
        $item = $data->$getter();

        if (!$item && !empty($parent)) {
            $data = $parent->{'get' . ucfirst($this->getName())}();

            return $this->doGetDiffDataForEditmode($data, $getter, $params, $level + 1);
        }

        if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
            return null;
        }

        if (!$collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
            return null;
        }

        $result = [];

        foreach ($collectionDef->getFieldDefinitions() as $fd) {
            $fieldData = $this->getDiffDataForField($item, $fd->getName(), $fd, $level, $data->getObject(), $getter, $params = []);

            $diffdata = [];

            foreach ($fieldData as $subdata) {
                $diffdata['field'] = $this->getName();
                $diffdata['key'] = $this->getName() . '~' . $fd->getName();
                $diffdata['value'] = $subdata['value'];
                $diffdata['type'] = $subdata['type'];
                $diffdata['disabled'] = $subdata['disabled'];

                // this is not needed anymoe
                unset($subdata['type']);
                unset($subdata['value']);

                $diffdata['title'] = $this->getName() . ' / ' . $subdata['title'];
                $brickdata = [
                    'brick' => substr($getter, 3),
                    'name' => $fd->getName(),
                    'subdata' => $subdata,
                ];
                $diffdata['data'] = $brickdata;
            }

            $result[] = $diffdata;
        }

        return $result;
    }

    /** See parent class.
     *
     */
    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $editmodeData = [];

        if ($data instanceof DataObject\Objectbrick) {
            $getters = $data->getBrickGetters();

            foreach ($getters as $getter) {
                $brickdata = $this->doGetDiffDataForEditmode($data, $getter, $params);
                if ($brickdata) {
                    foreach ($brickdata as $item) {
                        $editmodeData[] = $item;
                    }
                }
            }
        }

        return $editmodeData;
    }

    /**
     * See parent class.
     *
     *
     */
    public function getDiffDataFromEditmode(array $data, DataObject\Concrete $object = null, array $params = []): mixed
    {
        $valueGetter = 'get' . ucfirst($this->getName());
        $valueSetter = 'set' . ucfirst($this->getName());
        $brickdata = $object->$valueGetter();

        foreach ($data as $item) {
            $subdata = $item['data'];
            if (!$subdata) {
                continue;
            }
            $brickname = $subdata['brick'];

            $getter = 'get' . ucfirst($brickname);
            $setter = 'set' . ucfirst($brickname);

            $brick = $brickdata->$getter();
            if (!$brick) {
                // brick must be added to object
                $brickClass = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickname);
                $brick = new $brickClass($object);
            }

            $fieldname = $subdata['name'];
            $fielddata = [$subdata['subdata']];

            $collectionDef = DataObject\Objectbrick\Definition::getByKey($brickname);

            $fd = $collectionDef->getFieldDefinition($fieldname);
            if ($fd && $fd->isDiffChangeAllowed($object, $params)) {
                $value = $fd->getDiffDataFromEditmode($fielddata, $object, $params);
                $brick->setValue($fieldname, $value);

                $brickdata->$setter($brick);
            }

            $object->$valueSetter($brickdata);
        }

        return $brickdata;
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);

        if ($data instanceof DataObject\Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {
                if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
                    continue;
                }

                if (!$collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
                    continue;
                }

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

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Objectbricks $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->allowedTypes = $mainDefinition->allowedTypes;
        $this->maxItems = $mainDefinition->maxItems;
    }

    /**
     * This method is called in DataObject\ClassDefinition::save() and is used to create the database table for the localized data
     *
     */
    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        foreach ($this->allowedTypes as $allowedType) {
            $definition = DataObject\Objectbrick\Definition::getByKey($allowedType);
            if ($definition) {
                $definition->getDao()->createUpdateTable($class);
                $fieldDefinition = $definition->getFieldDefinitions();

                foreach ($fieldDefinition as $fd) {
                    if ($fd instanceof ClassSavedInterface) {
                        // defer creation
                        $fd->classSaved($class, $params);
                    }
                }

                $definition->getDao()->classSaved($class);
            }
        }
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $container
     * @param CalculatedValue[] $list
     */
    public static function collectCalculatedValueItems(array $container, array &$list = []): void
    {
        foreach ($container as $childDef) {
            if ($childDef instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
                $list[] = $childDef;
            }
        }
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Objectbrick::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Objectbrick::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Objectbrick::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Objectbrick::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Objectbrick) {
            $result = [];
            $value = $value->getObjectVars();
            /** @var Objectbrick\Data\AbstractData $item */
            foreach ($value as $item) {
                if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
                    continue;
                }

                $type = $item->getType();
                $result[$type] = [];
                $brickDef = Objectbrick\Definition::getByKey($type);
                $fds = $brickDef->getFieldDefinitions();
                foreach ($fds as $fd) {
                    $value = $item->{'get' . $fd->getName()}();
                    if ($fd instanceof NormalizerInterface
                        && $fd instanceof DataObject\ClassDefinition\Data) {
                        $result[$type][$fd->getName()] = $fd->normalize($value, $params);
                    } else {
                        throw new Exception($fd->getName() . ' does not implement NormalizerInterface');
                    }
                }
            }

            return $result;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $v) {
                $brickDef = Objectbrick\Definition::getByKey($key);
                $fds = $brickDef->getFieldDefinitions();

                $result[$key] = [];

                foreach ($v as $fieldKey => $fieldValue) {
                    $fd = $fds[$fieldKey];
                    if ($fd instanceof NormalizerInterface) {
                        $fieldValue = $fd->denormalize($fieldValue, $params);
                    }
                    $result[$key][$fieldKey] = $fieldValue;
                }
            }

            return $result;
        }

        return null;
    }

    public function getFieldType(): string
    {
        return 'objectbricks';
    }
}
