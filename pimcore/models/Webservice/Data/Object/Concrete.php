<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Webservice\Data\Object;

use Pimcore\Model;
use Pimcore\Model\Webservice;

class Concrete extends Model\Webservice\Data\Object {

    /**
     * @var Webservice\Data\Object\Element[]
     */
    public $elements;


    /**
     * @var string
     */
    public $className;

    /**
     * @param $object
     * @param null $options
     */
    public function map($object, $options = null) {
        parent::map($object);

        $this->className = $object->getClassName();

        $fd = $object->getClass()->getFieldDefinitions();

        foreach ($fd as $field) {

            $getter = "get".ucfirst($field->getName());

            //only expose fields which have a get method 
            if(method_exists($object,$getter)){
                $el = new Webservice\Data\Object\Element();
                $el->name = $field->getName();
                $el->type = $field->getFieldType();

                $el->value = $field->getForWebserviceExport($object);
                if ($el->value == null && self::$dropNullValues) {
                    continue;
                }

                $this->elements[] = $el;
            }

        }
    }

    /**
     * @param $object
     * @param bool $disableMappingExceptions
     * @param null $idMapper
     * @throws \Exception
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null) {

        $keys = get_object_vars($this);
        foreach ($keys as $key => $value) {
            $method = "set" . $key;
            if (method_exists($object, $method)) {
                $object->$method($value);
            }
        }

        //must be after generic setters above!!
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);

        if (is_array($this->elements)) {
            foreach ($this->elements as $element) {
                $class = $object->getClass();

                $setter = "set" . ucfirst($element->name);
                if (method_exists($object, $setter)) {
                    $tag = $object->getClass()->getFieldDefinition($element->name);
                    if($tag) {
                        if ($class instanceof Model\Object\ClassDefinition\Data\Fieldcollections) {
                            $object->$setter($tag->getFromWebserviceImport($element->fieldcollection, $object,
                                $idMapper));
                        } else {
                            $object->$setter($tag->getFromWebserviceImport($element->value, $object, $idMapper));
                        }
                    } else {
                        \Logger::error("tag for field " . $element->name . " not found");
                    }

                } else {
                    if(!$disableMappingExceptions) {
                        throw new \Exception("No element [ " . $element->name . " ] of type [ " . $element->type . " ] found in class definition " . $class);
                    }
                }

            }
        }
    }
}
