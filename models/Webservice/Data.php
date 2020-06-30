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

namespace Pimcore\Model\Webservice;

use Carbon\Carbon;
use Pimcore\Model;
use Pimcore\Model\Element;
use Pimcore\Model\Webservice;

/**
 * @deprecated
 */
abstract class Data
{
    /**
     * @param mixed $object
     * @param array|null $options
     *
     * @throws \Exception
     */
    public function map($object, $options = null)
    {
        $keys = get_object_vars($this);
        $blockedKeys = ['childs', 'fieldDefinitions'];

        if ($this instanceof \Pimcore\Model\Webservice\Data\Asset\File && isset($options['LIGHT']) && $options['LIGHT']) {
            $blockedKeys[] = 'data';
        }

        if ($object instanceof Model\Document\Tag\Relations) {
            $blockedKeys[] = 'value';
        }

        foreach ($keys as $key => $value) {
            $method = 'get' . $key;
            if (method_exists($object, $method) && !in_array($key, $blockedKeys)) {
                if ($object->$method()) {
                    $this->$key = $object->$method();

                    // check for a pimcore data type
                    if ($this->$key instanceof Element\ElementInterface) {
                        $this->$key = $this->$key->getId();
                    }

                    // if the value is an object or array call the mapper again for the value
                    if (is_object($this->$key) || is_array($this->$key)) {
                        $type = 'out';
                        if (strpos(get_class($this), '_In') !== false) {
                            $type = 'in';
                        }
                        $className = Webservice\Data\Mapper::findWebserviceClass($this->$key, 'out');
                        $this->$key = Webservice\Data\Mapper::map($this->$key, $className, $type);
                    }
                }
            }
        }

        if ($object instanceof Element\ElementInterface) {
            // add notes and events
            $list = new Element\Note\Listing();

            $cid = $object->getId();
            $ctype = Element\Service::getElementType($object);
            $condition = '(cid = ' . $list->quote($cid) . ' AND ctype = ' . $list->quote($ctype) . ')';
            $list->setCondition($condition);

            $list = $list->load();

            $noteList = [];
            if (is_array($list)) {
                foreach ($list as $note) {
                    $noteList[] = Element\Service::getNoteData($note);
                }
            }
            $this->{'notes'} = $noteList;
        }
    }

    /**
     * @param array $value
     *
     * @return \Pimcore\Model\Property[]
     */
    private function mapProperties($value)
    {
        if (is_array($value)) {
            $result = [];

            foreach ($value as $property) {
                if ($property instanceof \stdClass) {
                    $newProperty = new Model\Property();
                    $vars = get_object_vars($property);
                    foreach ($vars as $varName => $varValue) {
                        $method = 'set' . ucfirst($varName);
                        $newProperty->$method($property->$varName);
                    }
                    $result[] = $newProperty;
                } else {
                    $result[] = $property;
                }
            }
            $value = $result;
        }

        return $value;
    }

    /**
     * @param mixed $object
     * @param bool $disableMappingExceptions
     * @param Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null)
    {
        $keys = get_object_vars($this);
        foreach ($keys as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($object, $method)) {
                if ($object instanceof Element\ElementInterface && $key == 'properties') {
                    $value = $this->mapProperties($value);
                }
                $object->$method($value);
            }
        }

        if ($object instanceof Element\AbstractElement) {
            // Classes do not have properties
            $object->setProperties(null);
        }

        if (isset($this->properties) && (is_array($this->properties) || $this->properties instanceof \stdClass)) {
            foreach ($this->properties as $propertyWs) {
                $propertyWs = (array) $propertyWs;

                $type = $propertyWs['type'];
                if (in_array($type, ['object', 'document', 'asset'])) {
                    $id = $propertyWs['data'];
                    $type = $propertyWs['type'];
                    $dat = null;
                    if ($idMapper) {
                        $id = $idMapper->getMappedId($type, $id);
                    }

                    if ($id) {
                        $dat = Element\Service::getElementById($type, $id);
                    }

                    if (is_numeric($propertyWs['data']) and !$dat) {
                        if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                            throw new \Exception('cannot import property [ ' . $type . ' ] because it references unknown ' . $propertyWs['data']);
                        } else {
                            $idMapper->recordMappingFailure('object', $object->getId(), $type, $propertyWs['data']);
                        }
                    }
                } elseif ($type == 'date') {
                    $dat = Carbon::createFromTimestamp(strtotime($propertyWs['data']));
                } else {
                    $dat = $propertyWs['data'];
                }

                $object->setProperty($propertyWs['name'], $propertyWs['type'], $dat, $propertyWs['inherited'], $propertyWs['inheritable']);
            }
        }
    }
}
