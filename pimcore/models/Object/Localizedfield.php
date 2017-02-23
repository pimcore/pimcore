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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Object\Localizedfield\Dao getDao()
 */
class Localizedfield extends Model\AbstractModel
{
    const STRICT_DISABLED = 0;

    const STRICT_ENABLED = 1;

    /**
     * @var bool
     */
    private static $getFallbackValues = false;

    /**
     * @var array
     */
    public $items = [];

    /**
     * @var Model\Object\Concrete
     */
    public $object;

    /**
     * @var Model\Object\ClassDefinition
     */
    public $class;

    /** @var mixed  */
    public $context;

    /**
     * @var bool
     */
    private static $strictMode;

    /**
     * @param boolean $getFallbackValues
     */
    public static function setGetFallbackValues($getFallbackValues)
    {
        self::$getFallbackValues = $getFallbackValues;
    }

    /**
     * @return boolean
     */
    public static function getGetFallbackValues()
    {
        return self::$getFallbackValues;
    }

    /**
     * @return boolean
     */
    public static function isStrictMode()
    {
        return self::$strictMode;
    }

    /**
     * @param boolean $strictMode
     */
    public static function setStrictMode($strictMode)
    {
        self::$strictMode = $strictMode;
    }


    /**
     * @return boolean
     */
    public static function doGetFallbackValues()
    {
        return self::$getFallbackValues;
    }

    /**
     * @param array $items
     */
    public function __construct($items = null)
    {
        if ($items) {
            $this->setItems($items);
        }
    }

    /**
     * @param  $item
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    /**
     * @param  array $items
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Concrete $object
     * @return $this
     */
    public function setObject($object)
    {
        if ($object && !$object instanceof Concrete) {
            throw new \Exception("must be instance of object concrete");
        }
        $this->object = $object;
        //$this->setClass($this->getObject()->getClass());
        return $this;
    }

    /**
     * @return Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Model\Object\ClassDefinition $class
     * @return $this
     */
    public function setClass(ClassDefinition $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return Model\Object\ClassDefinition
     */
    public function getClass()
    {
        if (!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }

        return $this->class;
    }

    /**
     * @throws \Exception
     * @param null $language
     * @return string
     */
    public function getLanguage($language = null)
    {
        if ($language) {
            return (string) $language;
        }

        // try to get the language from the service container
        try {
            $locale = \Pimcore::getContainer()->get("pimcore.locale")->findLocale();
            if (Tool::isValidLanguage($locale)) {
                return (string) $locale;
            }
            throw new \Exception("Not supported language");
        } catch (\Exception $e) {
            return Tool::getDefaultLanguage();
        }
    }

    /**
     * @param $language
     * @return bool
     */
    public function languageExists($language)
    {
        return array_key_exists($language, $this->getItems());
    }

    /**
     * @param $name
     * @param null $language
     * @param bool $ignoreFallbackLanguage
     * @return mixed
     */
    public function getLocalizedValue($name, $language = null, $ignoreFallbackLanguage = false)
    {
        $data = null;
        $language = $this->getLanguage($language);

        $context = $this->getContext();
        if ($context && $context["containerType"] == "fieldcollection") {
            $containerKey = $context["containerKey"];
            $container = Model\Object\Fieldcollection\Definition::getByKey($containerKey);
        } else {
            $object = $this->getObject();
            $container = $object->getClass();
        }
        $fieldDefinition = $container->getFieldDefinition("localizedfields")->getFieldDefinition($name);

        if ($fieldDefinition instanceof Model\Object\ClassDefinition\Data\CalculatedValue) {
            $valueData = new Model\Object\Data\CalculatedValue($fieldDefinition->getName());
            $valueData->setContextualData("localizedfield", "localizedfields", null, $language);
            $data = Service::getCalculatedFieldValue($this->getObject(), $valueData);

            return $data;
        }

        if ($this->languageExists($language)) {
            if (array_key_exists($name, $this->items[$language])) {
                $data = $this->items[$language][$name];
            }
        }


        // check for inherited value
        $doGetInheritedValues = AbstractObject::doGetInheritedValues();
        if ($fieldDefinition->isEmpty($data) && $doGetInheritedValues) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {
                if ($object->getParent() instanceof AbstractObject) {
                    $parent = $object->getParent();
                    while ($parent && $parent->getType() == "folder") {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == "object" || $parent->getType() == "variant")) {
                        if ($parent->getClassId() == $object->getClassId()) {
                            $method = "getLocalizedfields";
                            if (method_exists($parent, $method)) {
                                $localizedFields = $parent->getLocalizedFields();
                                if ($localizedFields instanceof Localizedfield) {
                                    if ($localizedFields->object->getId() != $this->object->getId()) {
                                        $data = $localizedFields->getLocalizedValue($name, $language, true);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // check for fallback value
        if ($fieldDefinition->isEmpty($data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            foreach (Tool::getFallbackLanguagesFor($language) as $l) {
                if ($this->languageExists($l)) {
                    if (array_key_exists($name, $this->items[$l])) {
                        if ($data = $this->getLocalizedValue($name, $l)) {
                            break;
                        }
                    }
                }
            }
        }

        if ($fieldDefinition && method_exists($fieldDefinition, "preGetData")) {
            $data =  $fieldDefinition->preGetData($this, [
                "data" => $data,
                "language" => $language,
                "name" => $name
            ]);
        }

        return $data;
    }

    /**
     * @param $name
     * @param $value
     * @param null $language
     * @return $this
     */
    public function setLocalizedValue($name, $value, $language = null)
    {
        if (self::$strictMode) {
            if (!$language || !in_array($language, Tool::getValidLanguages())) {
                throw new \Exception("Language " . $language . " not accepted in strict mode");
            }
        }

        $language  = $this->getLanguage($language);
        if (!$this->languageExists($language)) {
            $this->items[$language] = [];
        }

        $contextInfo = $this->getContext();
        if ($contextInfo && $contextInfo["containerType"] == "block") {
            $classId = $contextInfo["classId"];
            $containerDefinition = ClassDefinition::getById($classId);
            $blockDefinition = $containerDefinition->getFieldDefinition($contextInfo["fieldname"]);

            $fieldDefinition = $blockDefinition->getFieldDefinition("localizedfields");
        } else {
            if ($contextInfo && $contextInfo["containerType"] == "fieldcollection") {
                $containerKey = $contextInfo["containerKey"];
                $containerDefinition = Fieldcollection\Definition::getByKey($containerKey);
            } else {
                $containerDefinition = $this->getObject()->getClass();
            }

            $localizedFieldDefinition = $containerDefinition->getFieldDefinition("localizedfields");
            $fieldDefinition = $localizedFieldDefinition->getFieldDefinition($name);
        }




        if (method_exists($fieldDefinition, "preSetData")) {
            $value =  $fieldDefinition->preSetData($this, $value, [
                "language" => $language,
                "name" => $name
            ]);
        }

        $this->items[$language][$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ["items", "context"];
    }

        /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}
