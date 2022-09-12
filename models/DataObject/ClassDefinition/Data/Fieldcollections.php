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

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

class Fieldcollections extends Data implements CustomResourcePersistingInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface, NormalizerInterface, DataContainerAwareInterface, IdRewriterInterface, PreGetDataInterface, PreSetDataInterface
{
    use DataObject\Traits\ClassSavedTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'fieldcollections';

    /**
     * @internal
     *
     * @var array
     */
    public $allowedTypes = [];

    /**
     * @internal
     *
     * @var bool
     */
    public $lazyLoading;

    /**
     * @internal
     *
     * @var int|null
     */
    public $maxItems;

    /**
     * @internal
     *
     * @var bool
     */
    public $disallowAddRemove = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $disallowReorder = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $collapsed;

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
    public $border = false;

    /**
     * @return bool
     */
    public function getLazyLoading()
    {
        return $this->lazyLoading;
    }

    /**
     * @param bool $lazyLoading
     *
     * @return $this
     */
    public function setLazyLoading($lazyLoading)
    {
        $this->lazyLoading = (bool) $lazyLoading;

        return $this;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Fieldcollection|null $data
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

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    $collectionData = [];

                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if (!$fd instanceof CalculatedValue) {
                            $value = $item->{'get' . $fd->getName()}();
                            if (isset($params['context']['containerKey']) === false) {
                                $params['context']['containerKey'] = $idx;
                            }
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
     * @param array|null $data
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
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        return 'NOT SUPPORTED';
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function load($object, $params = [])
    {
        $container = new DataObject\Fieldcollection([], $this->getName());
        $container->load($object);

        if ($container->isEmpty()) {
            return null;
        }

        return $container;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object, $params = [])
    {
        $container = new DataObject\Fieldcollection([], $this->getName());
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
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
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

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
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

    /**
     * { @inheritdoc }
     */
    public function preGetData(/** mixed */ $container, /** array */ $params = []) // : mixed
    {
        if (!$container instanceof DataObject\Concrete) {
            throw new \Exception('Field Collections are only valid in Objects');
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

    /**
     * { @inheritdoc }
     */
    public function preSetData(/** mixed */ $container, /**  mixed */ $data, /** array */ $params = []) // : mixed
    {
        $this->markLazyloadedFieldAsLoaded($container);

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
     * {@inheritdoc}
     */
    public function getGetterCode($class)
    {
        // getter, no inheritance here, that's the only difference
        $key = $this->getName();

        $code = '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . '()' . "\n";
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

    /** Generates a pretty version preview (similar to getVersionPreview) can be either HTML or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Fieldcollection|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array
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
     * { @inheritdoc }
     */
    public function rewriteIds(/** mixed */ $container, /** array */ $idMapping, /** array */ $params = []) /** :mixed */
    {
        $data = $this->getDataFromObjectParam($container, $params);

        if ($data instanceof DataObject\Fieldcollection) {
            foreach ($data as $item) {
                if (!$item instanceof DataObject\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                if ($collectionDef = DataObject\Fieldcollection\Definition::getByKey($item->getType())) {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if ($fd instanceof IdRewriterInterface) {
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
                        //TODO Pimcore 11 remove method_exists call
                        if (!$fd instanceof DataContainerAwareInterface && method_exists($fd, 'classSaved')) {
                            // defer creation
                            $fd->classSaved($class);
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
        $this->collapsed = (bool) $collapsed;
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
        $this->collapsible = (bool) $collapsible;
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
     * {@inheritdoc}
     */
    public function supportsInheritance()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Fieldcollection::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Fieldcollection::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Fieldcollection::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Fieldcollection::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
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

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $resultItems = [];
            foreach ($value as $idx => $itemData) {
                $type = $itemData['type'];
                $fcDef = DataObject\Fieldcollection\Definition::getByKey($type);

                $collectionClass = '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($type);
                /** @var DataObject\Fieldcollection\Data\AbstractData $collection */
                $collection = \Pimcore::getContainer()->get('pimcore.model.factory')->build($collectionClass);
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
}
