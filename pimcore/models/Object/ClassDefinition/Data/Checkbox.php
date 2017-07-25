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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    public $fieldtype = 'checkbox';

    /**
     * @var bool
     */
    public $defaultValue = 0;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'tinyint(1)';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'tinyint(1)';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'boolean';

    /**
     * @return int
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param int $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = (int)$defaultValue;

        return $this;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     *
     * @param bool $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_bool($data)) {
            $data = (int)$data;
        }

        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     *
     * @param bool $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (!is_null($data)) {
            $data = (bool) $data;
        }

        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     *
     * @param bool $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     *
     * @param bool $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromEditmode
     *
     * @param bool $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data === 'false') {
            return false;
        }

        return (bool)$this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     *
     * @param bool $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
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
        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        /* @todo seems to cause problems with old installations
        if(!is_bool($data) and $data !== 1 and $data !== 0){
        throw new \Exception(get_class($this).": invalid data");
        }*/
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
        $data = $this->getDataFromObjectParam($object, $params);

        return strval($data);
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
        return (bool)$importValue;
    }

    /**
     * @param Object\AbstractObject $object
     * @param array $params
     *
     * @return bool
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return (bool) $data;
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
        return (bool)$value;
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

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->defaultValue = $masterDefinition->defaultValue;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  $value
     * @param  $operator
     *
     * @return string
     *
     */
    public function getFilterCondition($value, $operator)
    {
        return $this->getFilterConditionExt(
            $value,
            $operator,
            [
                'name' => $this->name]
        );
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param $value
     * @param $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $db = \Pimcore\Db::get();
        $name = $params['name'] ? $params['name'] : $this->name;
        $value = $db->quote($value);
        $key = $db->quoteIdentifier($this->name, $name);

        return 'IFNULL(' . $key . ', 0) = ' . $value . ' ';
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }
}
