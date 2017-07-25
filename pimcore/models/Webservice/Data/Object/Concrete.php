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
 * @package    Webservice
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice\Data\Object;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Webservice;

class Concrete extends Model\Webservice\Data\Object
{
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
    public function map($object, $options = null)
    {
        parent::map($object);

        $this->className = $object->getClassName();

        $fd = $object->getClass()->getFieldDefinitions();

        foreach ($fd as $field) {
            $getter = 'get'.ucfirst($field->getName());

            //only expose fields which have a get method
            if (method_exists($object, $getter)) {
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
     *
     * @throws \Exception
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null)
    {
        $keys = get_object_vars($this);
        foreach ($keys as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($object, $method)) {
                $object->$method($value);
            }
        }

        //must be after generic setters above!!
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);

        if (is_array($this->elements)) {
            foreach ($this->elements as $element) {
                $class = $object->getClass();

                $setter = 'set' . ucfirst($element->name);
                if (method_exists($object, $setter)) {
                    $tag = $class->getFieldDefinition($element->name);
                    if ($tag) {
                        if ($class instanceof Model\Object\ClassDefinition\Data\Fieldcollections) {
                            $object->$setter($tag->getFromWebserviceImport(
                                $element->fieldcollection,
                                $object,
                                [],
                                $idMapper
                            ));
                        } else {
                            $object->$setter($tag->getFromWebserviceImport($element->value, $object, [], $idMapper));
                        }
                    } else {
                        Logger::error('tag for field ' . $element->name . ' not found');
                    }
                } else {
                    if (!$disableMappingExceptions) {
                        throw new \Exception('No element [ ' . $element->name . ' ] of type [ ' . $element->type . ' ] found in class definition [' . $class->getId() . '] | ' . $class->getName());
                    }
                }
            }
        }
    }
}
