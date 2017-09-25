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
use Pimcore\Model\Element;
use Pimcore\Tool\Serialize;

class Block extends Model\DataObject\ClassDefinition\Data
{
    use Element\ChildsCompatibilityTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'block';

    /**
     * @var bool
     */
    public $disallowAddRemove;

    /**
     * @var bool
     */
    public $disallowReorder;

    /**
     * @var bool
     */
    public $collapsible;

    /**
     * @var bool
     */
    public $collapsed;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'longtext';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'longtext';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\Block';

    /**
     * @var array
     */
    public $childs = [];

    /**
     * @var string
     */
    public $layout;

    /**
     * contains further child field definitions if there are more than one localized fields in on class
     *
     * @var array
     */
    protected $referencedFields = [];

    /**
     * @var array
     */
    public $fieldDefinitionsCache;

    /**
     * @see DataObject\ClassDefinition\Data::getDataForResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $result = [];

        if (is_array($data)) {
            foreach ($data as $blockElements) {
                $resultElement = [];

                /**
                 * @var  $blockElement DataObject\Data\BlockElement
                 */
                foreach ($blockElements as $elementName => $blockElement) {
                    /** @var $fd DataObject\ClassDefinition\Data */
                    $fd = $this->getFielddefinition($elementName);
                    if (!$fd) {
                        // class definition seems to have changed
                        Logger::warn('class definition seems to have changed, element name: ' . $elementName);
                        continue;
                    }
                    $elementData = $blockElement->getData();
                    $dataForResource = $fd->marshal($elementData, $object, ['raw' => true, 'blockmode' => true]);
                    //                    $blockElement->setData($fd->unmarshal($dataForResource, $object, ["raw" => true]));

                    // do not serialize the block element itself
                    $resultElement[$elementName] = [
                        'name' => $blockElement->getName(),
                        'type' => $blockElement->getType(),
                        'data' => $dataForResource
                    ];
                }
                $result[] = $resultElement;
            }
        }
        $result = Serialize::serialize($result);

        return $result;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data) {
            $unserializedData = unserialize($data);
            $result = [];

            foreach ($unserializedData as $blockElements) {
                $items = [];
                /** @var $blockElement DataObject\Data\BlockElement */
                foreach ($blockElements as $elementName => $blockElementRaw) {

                    /** @var $fd DataObject\ClassDefinition\Data */
                    $fd = $this->getFielddefinition($elementName);
                    if (!$fd) {
                        // class definition seems to have changed
                        Logger::warn('class definition seems to have changed, element name: ' . $elementName);
                        continue;
                    }

                    // do not serialize the block element itself
                    //                    $elementData = $blockElement->getData();
                    $elementData = $blockElementRaw['data'];

                    $dataFromResource = $fd->unmarshal($elementData, $object, ['raw' => true, 'blockmode' => true]);
                    $blockElementRaw['data'] = $dataFromResource;

                    if ($blockElementRaw['type'] == 'localizedfields') {
                        /** @var $data DataObject\Localizedfield */
                        $data = $blockElementRaw['data'];
                        if ($data) {
                            $data->setObject($object);
                            $data->setContext(['containerType' => 'block', 'containerKey' => $this->getName()]);
                            $blockElementRaw['data'] = $data;
                        }
                    }
                    $blockElement = new DataObject\Data\BlockElement($blockElementRaw['name'], $blockElementRaw['type'], $blockElementRaw['data']);
                    $items[$elementName] = $blockElement;
                }
                $result[] = $items;
            }

            return $result;
        }

        return null;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForQueryResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return null;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
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

                /**
                 * @var  $blockElement DataObject\Data\BlockElement
                 */
                foreach ($blockElements as $elementName => $blockElement) {
                    /** @var $fd DataObject\ClassDefinition\Data */
                    $fd = $this->getFielddefinition($elementName);
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
                    'data' => $resultElement
                ];
            }
        }

        return $result;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $result = [];
        $count = 0;

        foreach ($data as $rawBlockElement) {
            $resultElement = [];

            $oIndex = $rawBlockElement['oIndex'];
            $blockElement = $rawBlockElement['data'];

            foreach ($blockElement as $elementName => $elementData) {

                /** @var $fd DataObject\ClassDefinition\Data */
                $fd = $this->getFielddefinition($elementName);
                $dataFromEditMode = $fd->getDataFromEditmode(
                    $elementData,
                    $object,
                    [
                        'context' => [
                            'containerType' => 'block',
                            'fieldname' => $this->getName(),
                            'index' => $count,
                            'oIndex' => $oIndex,
                            'classId' => $object->getClassId()
                        ]
                    ]
                );

                $elementType = $fd->getFieldtype();

                $resultElement[$elementName] = new DataObject\Data\BlockElement($elementName, $elementType, $dataFromEditMode);
            }

            $result[] = $resultElement;
            $count++;
        }

        return $result;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getVersionPreview
     *
     * @param string $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return 'not supported';
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        return null;
    }

    /**
     * @param $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return null;
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        return 'not supported yet';
    }

    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        // do nothing
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     *
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            return 'not supported';
        }
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->disallowAddRemove = $masterDefinition->disallowAddRemove;
        $this->disallowReorder = $masterDefinition->disallowReorder;
        $this->collapsible = $masterDefinition->collapsible;
        $this->collapsed = $masterDefinition->collapsed;
    }

    /**
     * @param DataObject\Data\ExternalImage $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        if (is_null($data) || count($data) == 0) {
            return true;
        }

        return false;
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
     * @param mixed $child
     */
    public function addChild($child)
    {
        $this->childs[] = $child;
        $this->fieldDefinitionsCache = null;
    }

    /**
     * @param $layout
     *
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param mixed $data
     * @param array $blockedKeys
     *
     * @return $this
     */
    public function setValues($data = [], $blockedKeys = [])
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $blockedKeys)) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        return $this;
    }

    /**
     * @param null $def
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
            $fields[$def->getName()] = $def;
        }

        return $fields;
    }

    /**
     * @param array $context additional contextual data
     *
     * @return array
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

        if (isset($context['suppressEnrichment']) && $context['suppressEnrichment']) {
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
     * @param $name
     * @param array $context additional contextual data
     *
     * @return mixed
     */
    public function getFielddefinition($name, $context = [])
    {
        $fds = $this->getFieldDefinitions();
        if (isset($fds[$name])) {
            if (isset($context['suppressEnrichment']) && $context['suppressEnrichment']) {
                return $fds[$name];
            }
            $fieldDefinition = $this->doEnrichFieldDefinition($fds[$name], $context);

            return $fieldDefinition;
        }

        return;
    }

    public function doEnrichFieldDefinition($fieldDefinition, $context = [])
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
     * @return array
     */
    public function getReferencedFields()
    {
        return $this->referencedFields;
    }

    /**
     * @param $field
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
        unset($vars['fieldDefinitionsCache']);
        unset($vars['referencedFields']);

        return array_keys($vars);
    }

    /**
     * @param $data
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
                $fd = $this->getFielddefinition($elementName);
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

        if (!is_array($data)) {
            return $tags;
        }

        foreach ($data as $blockElements) {
            foreach ($blockElements as $elementName => $blockElement) {
                $fd = $this->getFielddefinition($elementName);
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
}
