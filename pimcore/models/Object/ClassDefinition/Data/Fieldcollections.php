<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice;
use Pimcore\Tool\Cast;

class Fieldcollections extends Model\Object\ClassDefinition\Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "fieldcollections";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "\\Object\\Fieldcollection";

    /**
     * @var string
     */
    public $allowedTypes = array();

    /**
     * @var boolean
     */
    public $lazyLoading;

    /**
     * @var int
     */
    public $maxItems;

    /**
     * @var boolean
     */
    public $disallowAddRemove;

    /**
     * @var boolean
     */
    public $disallowReorder;

    /**
     * @var boolean
     */
    public $collapsed;

    /**
     * @var boolean
     */
    public $collapsible;

    /**
     * @return boolean
     */
    public function getLazyLoading(){
        return $this->lazyLoading;
    }

    /**
     * @param  $lazyLoading
     * @return void
     */
    public function setLazyLoading($lazyLoading){
        $this->lazyLoading = $lazyLoading;
        return $this;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null)
    {

        $editmodeData = array();

        if ($data instanceof Object\Fieldcollection) {
            foreach ($data as $item) {

                if (!$item instanceof Object\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                try {
                    $collectionDef = Object\Fieldcollection\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                $collectionData = array();

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $collectionData[$fd->getName()] = $fd->getDataForEditmode($item->{$fd->getName()}, $object); 
                }

                $editmodeData[] = array(
                    "data" => $collectionData,
                    "type" => $item->getType()
                );
            }
        }

        return $editmodeData;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null)
    {
        $values = array();
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {

                $collectionData = array();
                $collectionDef = Object\Fieldcollection\Definition::getByKey($collectionRaw["type"]);

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    if (array_key_exists($fd->getName(),$collectionRaw["data"])) {
                        $collectionData[$fd->getName()] = $fd->getDataFromEditmode($collectionRaw["data"][$fd->getName()]);
                    }
                }

                $collectionClass = "\\Pimcore\\Model\\Object\\Fieldcollection\\Data\\" . ucfirst($collectionRaw["type"]);
                $collection = new $collectionClass;
                $collection->setValues($collectionData);
                $collection->setIndex($count);
                $collection->setFieldname($this->getName());

                $values[] = $collection;

                $count++;
            }
        }

        $container = new Object\Fieldcollection($values, $this->getName());
        return $container;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        return "FIELDCOLLECTIONS";
    }

    /**
     * @param Model\Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        return "NOT SUPPORTED";
    }

    /**
     * @param string $importValue
     * @return null
     */
    public function getFromCsvImport($importValue)
    {
        return;
    }

    /**
     * @param $object
     * @param array $params
     * @throws \Exception
     */
    public function save($object, $params = array())
    {
        $container = $this->getDataFromObjectParam($object);

        if ($container instanceof Object\Fieldcollection) {
            $container->save($object);
        }
    }

    /**
     * @param $object
     * @param array $params
     * @return null|Object\Fieldcollection
     */
    public function load($object, $params = array())
    {
        if (!$this->getLazyLoading() or $params["force"]) {
            $container = new Object\Fieldcollection(null, $this->getName());
            $container->load($object);

            if ($container->isEmpty()) {
                return null;
            }

            return $container;
        }

        return null;
    }

    /**
     * @param $object
     */
    public function delete($object)
    {
        $container = new Object\Fieldcollection(null, $this->getName());
        $container->delete($object);
    }

    /**
     * @return string
     */
    public function getAllowedTypes()
    {
        return $this->allowedTypes;
    }

    /**
     * @param $allowedTypes
     * @return $this
     */
    public function setAllowedTypes($allowedTypes)
    {
        if (is_string($allowedTypes)) {
            $allowedTypes = explode(",", $allowedTypes);
        }

        if (is_array($allowedTypes)) {
            for ($i = 0; $i < count($allowedTypes); $i++) {
                try {
                    Object\Fieldcollection\Definition::getByKey($allowedTypes[$i]);
                } catch (\Exception $e) {

                    \Logger::warn("Removed unknown allowed type [ $allowedTypes[$i] ] from allowed types of field collection");
                    unset($allowedTypes[$i]);
                }
            }
        }

        $this->allowedTypes = (array)$allowedTypes;
        return $this;
    }

    /**
     * @param Model\Object\AbstractObject $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        $data = $this->getDataFromObjectParam($object);
        $wsData = array();

        if ($data instanceof Object\Fieldcollection) {
            foreach ($data as $item) {

                if (!$item instanceof Object\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                $wsDataItem = new Webservice\Data\Object\Element();
                $wsDataItem->value = array();
                $wsDataItem->type = $item->getType();

                try {
                    $collectionDef = Object\Fieldcollection\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $el = new Webservice\Data\Object\Element();
                    $el->name = $fd->getName();
                    $el->type = $fd->getFieldType();
                    $el->value = $fd->getForWebserviceExport($item);
                    if ($el->value ==  null && self::$dropNullValues) {
                        continue;
                    }

                    $wsDataItem->value[] = $el;

                }

                $wsData[] = $wsDataItem;
            }
        }
        return $wsData;
    }

    /**
     * @param mixed $data
     * @param null $object
     * @param null $idMapper
     * @return mixed|Object\Fieldcollection
     * @throws \Exception
     */
    public function getFromWebserviceImport($data, $object = null, $idMapper = null)
    {
        $values = array();
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {

                if ($collectionRaw instanceof \stdClass) {
                    $collectionRaw = Cast::castToClass("\\Pimcore\\Model\\Webservice\\Data\\Object\\Element", $collectionRaw);
                }
                if (!$collectionRaw instanceof Webservice\Data\Object\Element) {

                    throw new \Exception("invalid data in fieldcollections [" . $this->getName() . "]");
                }

                $fieldcollection = $collectionRaw->type;
                $collectionData = array();
                $collectionDef = Object\Fieldcollection\Definition::getByKey($fieldcollection);

                if (!$collectionDef) {
                    throw new \Exception("Unknown fieldcollection in webservice import [" . $fieldcollection . "]");
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    foreach ($collectionRaw->value as $field) {
                        if ($field instanceof \stdClass) {
                            $field = Cast::castToClass("\\Pimcore\\Model\\Webservice\\Data\\Object\\Element", $field);
                        }
                        if (!$field instanceof Webservice\Data\Object\Element) {
                            throw new \Exception("invalid data in fieldcollections [" . $this->getName() . "]");
                        } else if ($field->name == $fd->getName()) {

                            if ($field->type != $fd->getFieldType()) {
                                throw new \Exception("Type mismatch for fieldcollection field [" . $field->name . "]. Should be [" . $fd->getFieldType() . "] but is [" . $field->type . "]");
                            }
                            $collectionData[$fd->getName()] = $fd->getFromWebserviceImport($field->value, $object, $idMapper);
                            break;
                        }


                    }

                }

                $collectionClass = "\\Pimcore\\Model\\Object\\Fieldcollection\\Data\\" . ucfirst($fieldcollection);
                $collection = new $collectionClass;
                $collection->setValues($collectionData);
                $collection->setIndex($count);
                $collection->setFieldname($this->getName());

                $values[] = $collection;

                $count++;
            }
        }

        $container = new Object\Fieldcollection($values, $this->getName());
        return $container;
    }

    /**
     * @param mixed $data
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = array();

        if ($data instanceof Object\Fieldcollection) {
            foreach ($data as $item) {

                if (!$item instanceof Object\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                try {
                    $collectionDef = Object\Fieldcollection\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $getter = "get" . ucfirst($fd->getName());
                    $dependencies = array_merge($dependencies, $fd->resolveDependencies($item->$getter()));
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
     * @return array
     */
    public function getCacheTags($data, $tags = array())
    {
        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Object\Fieldcollection) {
            foreach ($data as $item) {

                if (!$item instanceof Object\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                try {
                    $collectionDef = Object\Fieldcollection\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $getter = "get" . ucfirst($fd->getName());
                    $tags = $fd->getCacheTags($item->$getter(), $tags);
                }
            }
        }

        return $tags;
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false) {

        if(!$omitMandatoryCheck){
            if ($data instanceof Object\Fieldcollection) {
                foreach ($data as $item) {

                    if (!$item instanceof Object\Fieldcollection\Data\AbstractData) {
                        continue;
                    }

                    try {
                        $collectionDef = Object\Fieldcollection\Definition::getByKey($item->getType());
                    } catch (\Exception $e) {
                        continue;
                    }

                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $getter = "get" . ucfirst($fd->getName());
                        $fd->checkValidity($item->$getter());
                    }
                }
            }
        }
    }

    /**
     * @param $object
     * @param array $params
     * @return null|Object\Fieldcollection
     * @throws \Exception
     */
    public function preGetData ($object, $params = array()) {

        if(!$object instanceof Object\Concrete) {
            throw new \Exception("Field Collections are only valid in Objects");
        }

        $data = $object->{$this->getName()};
        if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
            $data = $this->load($object, array("force" => true));

            $setter = "set" . ucfirst($this->getName());
            if(method_exists($object, $setter)) {
                $object->$setter($data);
            }
        }
        return $data;
    }

    /**
     * @param $object
     * @param $data
     * @param array $params
     * @return array
     */
    public function preSetData ($object, $data, $params = array()) {

        if($data === null) $data = array();

        if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
            $object->addO__loadedLazyField($this->getName());
        }

        if ($data instanceof Object\Fieldcollection) {
            $data->setFieldname($this->getName());
        }

        return $data;
    }


    /**
     * @param $data
     * @param Object\Concrete $object
     * @return string
     */
    public function getDataForGrid($data, $object = null) {
        return "NOT SUPPORTED";
    }

    /**
     * @param $class
     * @return string
     */
    public function getGetterCode ($class) {
        // getter, no inheritance here, that's the only difference

        $key = $this->getName();
        $code = "";

        $code .= '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !\Pimcore::inAdmin()) { return $preValue;}' . "\n";

        if(method_exists($this,"preGetData")) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * @param $maxItems
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
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }


    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null) {
        $html = "";
        if ($data instanceof Object\Fieldcollection) {

            $html = "<table>";
            foreach ($data as $item) {
                if (!$item instanceof Object\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                $type = $item->getType();
                $html .= "<tr><th><b>" . $type . "</b></th><th>&nbsp;</th><th>&nbsp;</th></tr>";

                try {
                    $collectionDef = Object\Fieldcollection\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                $collectionData = array();

                foreach ($collectionDef->getFieldDefinitions() as $fd) {

                    $title = !empty($fd->title) ? $fd->title : $fd->getName();
                    $html .= "<tr><td>&nbsp;</td><td>" . $title . "</td><td>";
                    $html .= $fd->getVersionPreview($item->{$fd->getName()});
                    $html .= "</td></tr>";
                }
            }

            $html .= "</table>";
        }

        $value = array();
        $value["html"] = $html;
        $value["type"] = "html";
        return $value;
    }

    /**
     * @param $data
     * @param null $object
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null) {
        $result = parent::getDiffDataFromEditmode($data, $object);
        \Logger::debug("bla");
        return $result;
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
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = array()) {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof Object\Fieldcollection) {
            foreach ($data as $item) {
                if (!$item instanceof Object\Fieldcollection\Data\AbstractData) {
                    continue;
                }

                try {
                    $collectionDef = Object\Fieldcollection\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    if(method_exists($fd, "rewriteIds")) {
                        $d = $fd->rewriteIds($item, $idMapping, $params);
                        $setter = "set" . ucfirst($fd->getName());
                        $item->$setter($d);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        $this->allowedTypes = $masterDefinition->allowedTypes;
        $this->lazyLoading = $masterDefinition->lazyLoading;
        $this->maxItems = $masterDefinition->maxItems;
    }

    /**
     * This method is called in Object|Class::save() and is used to create the database table for the localized data
     * @return void
     */
    public function classSaved($class)
    {
        if (is_array($this->allowedTypes)) {
            foreach ($this->allowedTypes as $allowedType) {
                $definition = Object\Fieldcollection\Definition::getByKey($allowedType);
                if ($definition) {
                    $fieldDefinition = $definition->getFieldDefinitions();

                    foreach ($fieldDefinition as $fd) {
                        if (method_exists($fd, "classSaved")) {
                            $fd->classSaved($class);
                        }

                    }
                }
            }
        }
    }

    /**
     * @param boolean $disallowAddRemove
     */
    public function setDisallowAddRemove($disallowAddRemove)
    {
        $this->disallowAddRemove = $disallowAddRemove;
    }

    /**
     * @return boolean
     */
    public function getDisallowAddRemove()
    {
        return $this->disallowAddRemove;
    }

    /**
     * @param boolean $disallowReorder
     */
    public function setDisallowReorder($disallowReorder)
    {
        $this->disallowReorder = $disallowReorder;
    }

    /**
     * @return boolean
     */
    public function getDisallowReorder()
    {
        return $this->disallowReorder;
    }

    /**
     * @return boolean
     */
    public function isCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * @param boolean $collapsed
     */
    public function setCollapsed($collapsed)
    {
        $this->collapsed = $collapsed;
    }

    /**
     * @return boolean
     */
    public function isCollapsible()
    {
        return $this->collapsible;
    }

    /**
     * @param boolean $collapsible
     */
    public function setCollapsible($collapsible)
    {
        $this->collapsible = $collapsible;
    }



}
