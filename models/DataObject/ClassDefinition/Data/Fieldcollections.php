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
use Pimcore\Model\Webservice;
use Pimcore\Tool\Cast;

class Fieldcollections extends Data implements CustomResourcePersistingInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface
{
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'fieldcollections';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Fieldcollection';

    /**
     * @var array
     */
    public $allowedTypes = [];

    /**
     * @var bool
     */
    public $lazyLoading;

    /**
     * @var int
     */
    public $maxItems;

    /**
     * @var bool
     */
    public $disallowAddRemove;

    public $disallowReorder;

    /**
     * @var bool
     */
    public $collapsed;

    /**
     * @var bool
     */
    public $collapsible;

    /**
     * @var bool
     */
    public $border = false;

    /**
     * @return bool
     */
    public function getLazyLoading()
    {
        return $this->lazyLoading;
    }

    /**
     * @param  int|bool|null $lazyLoading
     *
     * @return $this
     */
    public function setLazyLoading($lazyLoading)
    {
        $this->lazyLoading = $lazyLoading;

        return $this;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $editmodeData = [];
        $idx = -1;

        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                $idx++;

                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    $collectionData = [];

                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if (!$fd instanceof CalculatedValue) {
                            $value = $item->{'get' . $fd->getName()}();
                            $collectionData[$fd->getName()] = $fd->getDataForEditmode($value, $object, $params);
                        }
                    }

                    $calculatedChilds = [];
                    self::collectCalculatedValueItems($collectionDef->getFieldDefinitions(), $calculatedChilds);

                    if ($calculatedChilds) {
                        foreach ($calculatedChilds as $fd) {
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
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Fieldcollection
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $values = [];
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {
                $collectionData = [];
                $collectionKey = $collectionRaw['type'];

                $oIndex = isset($collectionRaw['oIndex']) ? $collectionRaw['oIndex'] : null;

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
                $collection = \Pimcore::getContainer()->get('pimcore.model.factory')->build($collectionClass);
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
     * @see Data::getVersionPreview
     *
     * @param string $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return 'FIELDCOLLECTIONS';
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
     * @param mixed $params
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

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @throws \Exception
     */
    public function save($object, $params = [])
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
            throw new \Exception('Invalid value for field "' . $this->getName()."\" provided. You have to pass a DataObject\\Fieldcollection or 'null'");
        }
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return null|DataObject\Fieldcollection
     */
    public function load($object, $params = [])
    {
        $container = new DataObject\Fieldcollection(null, $this->getName());
        $container->load($object);

        if ($container->isEmpty()) {
            return null;
        }

        return $container;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     */
    public function delete($object, $params = [])
    {
        $container = new DataObject\Fieldcollection(null, $this->getName());
        $container->delete($object);
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

        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                $wsDataItem = new Webservice\Data\DataObject\Element();
                $wsDataItem->value = [];
                $wsDataItem->type = $item->getType();

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
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
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return mixed|DataObject\Fieldcollection
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($data, $object = null, $params = [], $idMapper = null)
    {
        $values = [];
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {
                if ($collectionRaw instanceof \stdClass) {
                    $collectionRaw = Cast::castToClass('\\Pimcore\\Model\\Webservice\\Data\\DataObject\\Element', $collectionRaw);
                }
                if (!$collectionRaw instanceof Webservice\Data\DataObject\Element) {
                    throw new \Exception('invalid data in fieldcollections [' . $this->getName() . ']');
                }

                $fieldcollection = $collectionRaw->type;
                $collectionData = [];
                $collectionDef = DataObject\Fieldcollection\Definition::getByKey($fieldcollection);

                if (!$collectionDef) {
                    throw new \Exception('Unknown fieldcollection in webservice import [' . $fieldcollection . ']');
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    foreach ($collectionRaw->value as $field) {
                        if ($field instanceof \stdClass) {
                            $field = Cast::castToClass('\\Pimcore\\Model\\Webservice\\Data\\DataObject\\Element', $field);
                        }
                        if (!$field instanceof Webservice\Data\DataObject\Element) {
                            throw new \Exception('invalid data in fieldcollections [' . $this->getName() . ']');
                        } elseif ($field->name == $fd->getName()) {
                            if ($field->type != $fd->getFieldType()) {
                                throw new \Exception('Type mismatch for fieldcollection field [' . $field->name . ']. Should be [' . $fd->getFieldType() . '] but is [' . $field->type . ']');
                            }

                            $params = [
                                'context' => [
                                    'object' => $object,
                                    'containerType' => 'fieldcollection',
                                    'containerKey' => $fieldcollection,
                                    'fieldname' => $fd->getName(),
                                    'index' => $count,
                                ], ];

                            $collectionData[$fd->getName()] = $fd->getFromWebserviceImport($field->value, $object, $params, $idMapper);
                            break;
                        }
                    }
                }

                $collectionClass = '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($fieldcollection);
                /** @var DataObject\Fieldcollection\Data\AbstractData $collection */
                $collection = \Pimcore::getContainer()->get('pimcore.model.factory')->build($collectionClass);
                $collection->setValues($collectionData);
                $collection->setIndex($count);
                $collection->setFieldname($this->getName());

                $values[] = $collection;

                $count++;
            }
        }

        $container = new DataObject\Fieldcollection($values, $this->getName());

        return $container;
    }

    /**
     * @param DataObject\Fieldcollection|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
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
                                    $fd->checkValidity($item->$getter());
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
                $aggregatedExceptions = new Model\Element\ValidationException();
                $aggregatedExceptions->setSubItems($validationExceptions);
                throw $aggregatedExceptions;
            }
        }
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return null|DataObject\Fieldcollection
     *
     * @throws \Exception
     */
    public function preGetData($object, $params = [])
    {
        if (!$object instanceof DataObject\Concrete) {
            throw new \Exception('Field Collections are only valid in Objects');
        }

        $data = $object->getObjectVar($this->getName());
        if ($this->getLazyLoading() && !$object->isLazyKeyLoaded($this->getName())) {
            $data = $this->load($object);
            if ($data instanceof Model\Element\DirtyIndicatorInterface) {
                $data->resetDirtyMap();
            }

            $setter = 'set' . ucfirst($this->getName());
            if (method_exists($object, $setter)) {
                $object->$setter($data);
                $this->markLazyloadedFieldAsLoaded($object);
            }
        }

        return $data;
    }

    /**
     * @param DataObject\Concrete $object
     * @param DataObject\Fieldcollection|null $data
     * @param array $params
     *
     * @return DataObject\Fieldcollection|null
     */
    public function preSetData($object, $data, $params = [])
    {
        $this->markLazyloadedFieldAsLoaded($object);

        if ($data instanceof DataObject\Fieldcollection) {
            $data->setFieldname($this->getName());
        }

        return $data;
    }

    /**
     * @param DataObject\Fieldcollection|null $data
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
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getGetterCode($class)
    {
        // getter, no inheritance here, that's the only difference

        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . " () {\n";

        $code .= $this->getPreGetValueHookCode($key);

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * @param int|string|null $maxItems
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

    /** Generates a pretty version preview (similar to getVersionPreview) can be either HTML or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Fieldcollection|null $data
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
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

        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if (method_exists($fd, 'rewriteIds')) {
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
     * @param DataObject\ClassDefinition\Data\Fieldcollections $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->allowedTypes = $masterDefinition->allowedTypes;
        $this->lazyLoading = $masterDefinition->lazyLoading;
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
            foreach ($this->allowedTypes as $i => $allowedType) {
                if ($definition = DataObject\Fieldcollection\Definition::getByKey($allowedType)) {
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
                } else {
                    Logger::warn("Removed unknown allowed type [ $allowedType ] from allowed types of field collection");
                    unset($this->allowedTypes[$i]);
                }
            }
        }
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
    public function getDisallowAddRemove()
    {
        return $this->disallowAddRemove;
    }

    /**
     * @param bool $disallowReorder
     */
    public function setDisallowReorder($disallowReorder)
    {
        $this->disallowReorder = $disallowReorder;
    }

    /**
     * @return bool
     */
    public function getDisallowReorder()
    {
        return $this->disallowReorder;
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
     * @param DataObject\ClassDefinition\Data[] $container
     * @param DataObject\ClassDefinition\Data[] $list
     */
    public static function collectCalculatedValueItems($container, &$list = [])
    {
        if (is_array($container)) {
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
    }

    /**
     * @inheritdoc
     */
    public function supportsInheritance()
    {
        return false;
    }
}
