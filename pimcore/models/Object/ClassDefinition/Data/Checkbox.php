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

class Checkbox extends Model\Object\ClassDefinition\Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "checkbox";

    /**
     * @var bool
     */
    public $defaultValue = 0;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "tinyint(1)";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "tinyint(1)";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "boolean";


    /**
     * @return integer
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param integer $defaultValue
     * @return void
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = (int)$defaultValue;
        return $this;
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param boolean $data
     * @param null|Object\AbstractObject $object
     * @return int
     */
    public function getDataForResource($data, $object = null)
    {

        if (is_bool($data)) {
            $data = (int)$data;
        }


        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param boolean $data
     * @return boolean
     */
    public function getDataFromResource($data)
    {
        if(!is_null($data)) {
            $data = (bool) $data;
        }
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param boolean $data
     * @param null|Object\AbstractObject $object
     * @return boolean
     */
    public function getDataForQueryResource($data, $object = null)
    {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param boolean $data
     * @param null|Object\AbstractObject $object
     * @return boolean
     */
    public function getDataForEditmode($data, $object = null)
    {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromEditmode
     * @param boolean $data
     * @param null|Object\AbstractObject $object
     * @return boolean
     */
    public function getDataFromEditmode($data, $object = null)
    {
        if ($data === "false") {
            return false;
        }
        return (bool)$this->getDataFromResource($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param boolean $data
     * @return boolean
     */
    public function getVersionPreview($data)
    {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {

        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new \Exception("Empty mandatory field [ " . $this->getName() . " ]");
        }

        /* @todo seems to cause problems with old installations
        if(!is_bool($data) and $data !== 1 and $data !== 0){
        throw new \Exception(get_class($this).": invalid data");
        }*/
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        $data = $this->getDataFromObjectParam($object);
        return strval($data);
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object\AbstractObject $abstract
     * @return Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue)
    {
        return (bool)$importValue;
    }

    public function getForWebserviceExport($object)
    {
        $data = $this->getDataFromObjectParam($object);
        return (bool) $data;
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null)
    {
        return (bool)$value;
    }


    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        $this->defaultValue = $masterDefinition->defaultValue;
    }

}
