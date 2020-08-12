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
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\Webservice;
use Pimcore\Tool;

class Objectbricks extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'objectbricks';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\' . Objectbrick::class;

    /**
     * @var array
     */
    public $allowedTypes = [];

    /**
     * @var int
     */
    public $maxItems;

    /**
     * @var bool
     */
    public $border = false;

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
     * @return bool
     */
    public function getBorder(): bool
    {
        return $this->border;
    }

    /**
     * @param bool $border
     */
    public function setBorder(bool $border): void
    {
        $this->border = $border;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
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
                    'fieldname' => $this->getName(),
                ];

                $editmodeData[] = $this->doGetDataForEditmode($getter, $data, $params, $allowedBrickType);
            }
        }

        return $editmodeData;
    }

    /**
     * @param string $getter
     * @param Objectbrick\Data\AbstractData $data
     * @param array|null $params
     * @param string $allowedBrickType
     * @param int $level
     *
     * @return array|null
     */
    private function doGetDataForEditmode($getter, $data, $params, $allowedBrickType, $level = 0)
    {
        $object = $data->getObject();
        if ($object) {
            $parent = DataObject\Service::hasInheritableParentObject($object);
        }
        /** @var DataObject\Objectbrick\Definition $item */
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

        $calculatedChilds = [];
        self::collectCalculatedValueItems($collectionDef->getFieldDefinitions(), $calculatedChilds);

        foreach ($calculatedChilds as $fd) {
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
     *
     * @param Objectbrick\Data\AbstractData $item
     * @param string $key
     * @param Data $fielddefinition
     * @param int $level
     * @param DataObject\Concrete|null $baseObject
     * @param string $getter
     * @param array|null $params
     *
     * @return mixed
     */
    private function getDataForField($item, $key, $fielddefinition, $level, $baseObject, $getter, $params)
    {
        $result = new \stdClass();
        if ($baseObject) {
            $parent = DataObject\Service::hasInheritableParentObject($baseObject);
        }
        $valueGetter = 'get' . ucfirst($key);

        // Editmode optimization for lazy loaded relations except metadata relations because they have additional data
        // which cannot be loaded directly from the DB.
        // Note that this is just for AbstractRelations, not for all LazyLoadingSupportInterface types.
        // It tries to optimize fetching the data needed for the editmode without loading the entire target element.

        if ((!isset($params['objectFromVersion']) || !$params['objectFromVersion'])
            && ($fielddefinition instanceof Data\Relations\AbstractRelations)
            && $fielddefinition->getLazyLoading()
            && !$fielddefinition instanceof ManyToManyObjectRelation
            && !$fielddefinition instanceof AdvancedManyToManyRelation
            && !$fielddefinition instanceof Block) {

            //lazy loading data is fetched from DB differently, so that not every relation object is instantiated
            if ($fielddefinition instanceof ReverseManyToManyObjectRelation) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refId = $fielddefinition->getOwnerClassId();
            } else {
                $refKey = $key;
                $refId = null;
            }

            $relations = $item->getRelationData($refKey, !$fielddefinition instanceof ReverseManyToManyObjectRelation, $refId);
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
                $data = $relations[0];
            } else {
                foreach ($relations as $rel) {
                    $data[] = ['id' => $rel['id'], 'fullpath' => $rel['path'],  'type' => $rel['type'], 'subtype' => $rel['subtype'], 'published' => ($rel['published'] ? true : false)];
                }
            }
            $result->objectData = $data;
            $result->metaData['objectid'] = $baseObject->getId();
            $result->metaData['inherited'] = $level != 0;
        } else {
            $fieldValue = null;
            $editmodeValue = null;
            if (!empty($item)) {
                $fieldValue = $item->$valueGetter();

                $editmodeValue = $fielddefinition->getDataForEditmode($fieldValue, $baseObject, $params);
            }
            if ($fielddefinition->isEmpty($fieldValue) && !empty($parent)) {
                $backup = DataObject\AbstractObject::getGetInheritedValues();
                DataObject\AbstractObject::setGetInheritedValues(true);
                $parentItem = $parent->{'get' . ucfirst($this->getName())}()->$getter();
                DataObject\AbstractObject::setGetInheritedValues($backup);
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
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Objectbrick\Data\AbstractData
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
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
     * @param Objectbrick\Data\AbstractData|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return 'BRICKS';
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
        return 'NOT SUPPORTED';
    }

    /**
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return null;
    }

    /**
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
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

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     */
    public function save($object, $params = [])
    {
        $container = $this->getDataFromObjectParam($object);
        if ($container instanceof DataObject\Objectbrick) {
            $container->save($object, $params);
        }
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return Objectbrick|null
     */
    public function load($object, $params = [])
    {
        $classname = '\\Pimcore\\Model\\DataObject\\' . ucfirst($object->getClass()->getName()) . '\\' . ucfirst($this->getName());

        if (Tool::classExists($classname)) {
            $container = new $classname($object, $this->getName());
            $container->load($object);

            return $container;
        }

        return null;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     */
    public function delete($object, $params = [])
    {
        $container = $this->load($object);
        if ($container) {
            $container->delete($object);
        }
    }

    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return $this->allowedTypes;
    }

    /**
     * @param string|array|null $allowedTypes
     *
     * @return $this
     */
    public function setAllowedTypes($allowedTypes)
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

    /**
     * @deprecated
     *
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        $wsData = [];

        if ($data instanceof DataObject\Objectbrick) {
            $data = $data->getObjectVars();
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Objectbrick\Data\AbstractData) {
                    continue;
                }

                $wsDataItem = new Webservice\Data\DataObject\Element();
                $wsDataItem->value = [];
                $wsDataItem->type = $item->getType();

                if ($collectionDef = DataObject\Objectbrick\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $el = new Webservice\Data\DataObject\Element();
                        $el->name = $fd->getName();
                        $el->type = $fd->getFieldType();
                        $el->value = $fd->getForWebserviceExport($item, $params);
                        if ($el->value == null && self::$dropNullValues) {
                            continue;
                        }

                        $wsDataItem->value[] = $el;
                    }

                    $wsData[] = $wsDataItem;
                }
            }
        }

        return $wsData;
    }

    /**
     * @deprecated
     *
     * @param array $data
     * @param DataObject\Concrete $relatedObject
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return Objectbrick|null
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($data, $relatedObject = null, $params = [], $idMapper = null)
    {
        $containerName = '\\Pimcore\\Model\\DataObject\\' . ucfirst($relatedObject->getClass()->getName()) . '\\' . ucfirst($this->getName());

        if (Tool::classExists($containerName)) {
            $container = new $containerName($relatedObject, $this->getName());

            if (is_array($data)) {
                foreach ($data as $collectionRaw) {
                    if ($collectionRaw instanceof \stdClass) {
                        $class = '\\Pimcore\\Model\\Webservice\\Data\\DataObject\\Element';
                        $collectionRaw = Tool\Cast::castToClass($class, $collectionRaw);
                    }

                    if ($collectionRaw != null) {
                        if (!$collectionRaw instanceof Webservice\Data\DataObject\Element) {
                            throw new \Exception('invalid data in objectbrick [' . $this->getName() . ']');
                        }

                        $brick = $collectionRaw->type;
                        $collectionData = [];
                        $collectionDef = DataObject\Objectbrick\Definition::getByKey($brick);

                        if (!$collectionDef) {
                            throw new \Exception('Unknown objectbrick in webservice import [' . $brick . ']');
                        }

                        foreach ($collectionDef->getFieldDefinitions() as $fd) {
                            foreach ($collectionRaw->value as $field) {
                                if ($field instanceof \stdClass) {
                                    $class = '\\Pimcore\\Model\\Webservice\\Data\\DataObject\\Element';
                                    $field = Tool\Cast::castToClass($class, $field);
                                }
                                if (!$field instanceof Webservice\Data\DataObject\Element) {
                                    throw new \Exception('invalid data in objectbricks [' . $this->getName() . ']');
                                } elseif ($field->name == $fd->getName()) {
                                    if ($field->type != $fd->getFieldType()) {
                                        throw new \Exception('Type mismatch for objectbricks field [' . $field->name . ']. Should be [' . $fd->getFieldType() . '] but is [' . $field->type . ']');
                                    }
                                    $collectionData[$fd->getName()] = $fd->getFromWebserviceImport($field->value, $relatedObject, $params, $idMapper);
                                    break;
                                }
                            }
                        }

                        $collectionClass = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brick);
                        $collection = new $collectionClass($relatedObject);
                        $collection->setValues($collectionData);
                        $collection->setFieldname($this->getName());

                        $setter = 'set' . ucfirst($brick);

                        $container->$setter($collection);
                    }
                }
            }

            return $container;
        }

        return null;
    }

    /**
     * @param DataObject\Concrete $object
     * @param Objectbrick|null $value
     * @param array $params
     *
     * @return mixed
     */
    public function preSetData($object, $value, $params = [])
    {
        if ($value instanceof DataObject\Objectbrick) {
            $value->setFieldname($this->getName());
        }

        return $value;
    }

    /**
     * @param Objectbrick|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
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

    /**
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getGetterCode($class)
    {
        // getter

        if ($class->getGenerateTypeDeclarations() && $this->getReturnTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $key = $this->getName();
        $code = '';

        $classname = '\\Pimcore\\Model\\DataObject\\' . ucfirst($class->getName()) . '\\' . ucfirst($this->getName());

        $code .= '/**' . "\n";
        $code .= '* @return ' . $classname . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ()' . $typeDeclaration .  " {\n";

        $code .= "\t" . '$data = $this->' . $key . ";\n";
        $code .= "\t" . 'if(!$data) { ' . "\n";

        $code .= "\t\t" . 'if(\Pimcore\Tool::classExists("' . str_replace('\\', '\\\\', $classname) . '")) { ' . "\n";
        $code .= "\t\t\t" . '$data = new ' . $classname . '($this, "' . $key . '");' . "\n";
        $code .= "\t\t\t" . '$this->' . $key . ' = $data;' . "\n";
        $code .= "\t\t" . '} else {' . "\n";
        $code .= "\t\t\t" . 'return null;' . "\n";
        $code .= "\t\t" . '}' . "\n";
        $code .= "\t" . '}' . "\n";

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        }

        $code .= $this->getPreGetValueHookCode($key);

        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
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
                            try {
                                $key = $fd->getName();
                                $getter = 'get' . ucfirst($key);
                                $fd->checkValidity($item->$getter());
                            } catch (Model\Element\ValidationException $ve) {
                                $ve->addContext($this->getName());
                                $validationExceptions[] = $ve;
                            }
                        }
                    }
                }
            }

            if ($validationExceptions) {
                $aggregatedExceptions = new Model\Element\ValidationException('invalid brick ' . $this->getName());
                $aggregatedExceptions->setSubItems($validationExceptions);
                throw $aggregatedExceptions;
            }
        }
    }

    /**
     * @param Objectbrick|null $data
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return 'NOT SUPPORTED';
    }

    /**
     * @param Objectbrick\Data\AbstractData $item
     * @param string $key
     * @param Data $fielddefinition
     * @param int $level
     * @param DataObject\Concrete $baseObject
     * @param string $getter
     * @param array $params
     *
     * @return mixed
     */
    private function getDiffDataForField($item, $key, $fielddefinition, $level, $baseObject, $getter, $params = [])
    {
        $valueGetter = 'get' . ucfirst($key);

        $value = $fielddefinition->getDiffDataForEditmode($item->$valueGetter(), $baseObject, $params);

        return $value;
    }

    /**
     * @param Objectbrick\Data\AbstractData $data
     * @param string $getter
     * @param array $params
     * @param int $level
     *
     * @return array|null
     */
    private function doGetDiffDataForEditmode($data, $getter, $params = [], $level = 0)
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
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
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
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
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
     * @return Model\Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

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
                    if (method_exists($fd, 'rewriteIds')) {
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
     * @param DataObject\ClassDefinition\Data\Objectbricks $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->allowedTypes = $masterDefinition->allowedTypes;
        $this->maxItems = $masterDefinition->maxItems;
    }

    /**
     * This method is called in DataObject\ClassDefinition::save() and is used to create the database table for the localized data
     *
     * @param DataObject\ClassDefinition $class
     * @param array $params
     */
    public function classSaved($class, $params = [])
    {
        if (is_array($this->allowedTypes)) {
            foreach ($this->allowedTypes as $allowedType) {
                $definition = DataObject\Objectbrick\Definition::getByKey($allowedType);
                if ($definition) {
                    $definition->getDao()->createUpdateTable($class);
                    $fieldDefinition = $definition->getFieldDefinitions();

                    foreach ($fieldDefinition as $fd) {
                        if (method_exists($fd, 'classSaved')) {
                            if (!$fd instanceof Localizedfields) {
                                // defer creation
                                $fd->classSaved($class);
                            }
                        }
                    }

                    $definition->getDao()->classSaved($class);
                }
            }
        }
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $container
     * @param CalculatedValue[] $list
     */
    public static function collectCalculatedValueItems($container, &$list = [])
    {
        if (is_array($container)) {
            /** @var DataObject\ClassDefinition\Data $childDef */
            foreach ($container as $childDef) {
                if ($childDef instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
                    $list[] = $childDef;
                }
            }
        }
    }
}
