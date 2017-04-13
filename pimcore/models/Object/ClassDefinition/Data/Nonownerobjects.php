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
 * @package    Object\ClassDefinition
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Object;

class Nonownerobjects extends Model\Object\ClassDefinition\Data\Objects
{
    /**
     * @var bool
     */
    public static $remoteOwner = true;

    /**
     * @return bool
     */
    public function isRemoteOwner()
    {
        return self::$remoteOwner;
    }

    /**
     * @var string
     */
    public $ownerClassName;

    /**
     * @var number
     */
    public $ownerClassId;

    /**
     * @var string
     */
    public $ownerFieldName;

    /**
     * NonOwnerObjects must be lazy loading!
     *
     * @var bool
     */
    public $lazyLoading = true;

    /**
     * @param array $classes
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        //dummy, classes are set from owner classId
        return $this;
    }

    /**
     * @return bool
     */
    public function getLazyLoading()
    {
        return true;
    }

    /**
     * @param  $lazyLoading
     *
     * @return $this
     */
    public function setLazyLoading($lazyLoading)
    {
        //dummy, non owner objects must be lazy loading
        return $this;
    }

    /**
     * @param string $ownerClassName
     *
     * @return $this
     */
    public function setOwnerClassName($ownerClassName)
    {
        $this->ownerClassName = $ownerClassName;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerClassName()
    {
        //fallback for legacy data
        if (empty($this->ownerClassName)) {
            try {
                $class = Object\ClassDefinition::getById($this->ownerClassId);
                $this->ownerClassName =  $class->getName();
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        return $this->ownerClassName;
    }

    /**
     * @return number
     */
    public function getOwnerClassId()
    {
        if (empty($this->ownerClassId)) {
            try {
                $class = Object\ClassDefinition::getByName($this->ownerClassName);
                $this->ownerClassId =  $class->getId();
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        return $this->ownerClassId;
    }

    /**
     * @return string
     */
    public function getOwnerFieldName()
    {
        return $this->ownerFieldName;
    }

    /**
     * @param  string $fieldName
     *
     * @return $this
     */
    public function setOwnerFieldName($fieldName)
    {
        $this->ownerFieldName = $fieldName;

        return $this;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     *
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     *
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return null;
    }

    /**
     *
     * Checks if an object is an allowed relation
     *
     * @param Model\Object\AbstractObject $object
     *
     * @return bool
     */
    protected function allowObjectRelation($object)
    {
        //only relations of owner type are allowed
        $ownerClass = Object\ClassDefinition::getByName($this->getOwnerClassName());
        if ($ownerClass->getId() > 0 and $ownerClass->getId() == $object->getClassId()) {
            $fd = $ownerClass->getFieldDefinition($this->getOwnerFieldName());
            if ($fd instanceof Object\ClassDefinition\Data\Objects) {
                return $fd->allowObjectRelation($object);
            }
        } else {
            return false;
        }
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
        //TODO
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (is_array($data)) {
            foreach ($data as $o) {
                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass or !($o instanceof Object\Concrete)) {
                    throw new Model\Element\ValidationException('Invalid non owner object relation to object ['.$o->getId().']', null, null);
                }
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Object\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        return '';
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @abstract
     *
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return null;
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
        return $tags;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        return [];
    }

    /**
     * @param Object\AbstractObject $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        return null;
    }

    /**
     * converts data to be imported via webservices
     *
     * @param mixed $value
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @param $idMapper
     *
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        return null;
    }
}
