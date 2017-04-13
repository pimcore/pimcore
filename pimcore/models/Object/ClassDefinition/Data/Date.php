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

class Date extends Model\Object\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'date';

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'bigint(20)';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'bigint(20)';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Carbon\\Carbon';

    /**
     * @var int
     */
    public $defaultValue;

    /**
     * @var bool
     */
    public $useCurrentDate;

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     *
     * @param \Zend_Date|\DateTime $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data) {
            return $data->getTimestamp();
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     *
     * @param int $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return \Zend_Date|\DateTime
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data) {
            return $this->getDateFromTimestamp($data);
        }

        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     *
     * @param \Zend_Date|\DateTime $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if ($data) {
            return $data->getTimestamp();
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     *
     * @param \Zend_Date\DateTime $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            return $data->getTimestamp();
        }
    }

    /**
     * @param $timestamp
     *
     * @return \DateTime|\Pimcore\Date
     */
    protected function getDateFromTimestamp($timestamp)
    {
        if (\Pimcore\Config::getFlag('zend_date')) {
            $date = new \Pimcore\Date($timestamp);
        } else {
            $date = new \Carbon\Carbon();
            $date->setTimestamp($timestamp);
        }

        return $date;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     *
     * @param int $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return \Zend_Date|\DateTime
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            return $this->getDateFromTimestamp($data / 1000);
        }

        return false;
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return int|null|string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data) {
            return $data->getTimestamp();
        } else {
            return null;
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     *
     * @param \Zend_Date|\DateTime $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof \Zend_Date) {
            return $data->get(\Zend_Date::DATE_MEDIUM);
        } elseif ($data instanceof \DateTimeInterface) {
            return $data->format('Y-m-d');
        }
    }

    /**
     * @return \Pimcore\Date
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return $this->defaultValue;
        } else {
            return 0;
        }
    }

    /**
     * @param mixed $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        if (strlen(strval($defaultValue)) > 0) {
            if (is_numeric($defaultValue)) {
                $this->defaultValue = (int)$defaultValue;
            } else {
                $this->defaultValue = strtotime($defaultValue);
            }
        }

        return $this;
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
        if ($data instanceof \Zend_Date) {
            return $data->toString('Y-m-d', 'php');
        } elseif ($data instanceof \DateTimeInterface) {
            return $data->format('Y-m-d');
        }

        return null;
    }

    /**
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return null|\Pimcore\Date|Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $timestamp = strtotime($importValue);
        if ($timestamp) {
            return $this->getDateFromTimestamp($timestamp);
        }

        return null;
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

    /**
     * converts data to be exposed via webservices
     *
     * @param Object\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        return $this->getForCsvExport($object, $params);
    }

    /**
     * @param mixed $value
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        $timestamp = strtotime($value);
        if (empty($value)) {
            return null;
        } elseif ($timestamp !== false) {
            return $this->getDateFromTimestamp($timestamp);
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }

    /**
     * @param $useCurrentDate
     *
     * @return $this
     */
    public function setUseCurrentDate($useCurrentDate)
    {
        $this->useCurrentDate = (bool)$useCurrentDate;

        return $this;
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

    /** See parent class.
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return null|\Pimcore\Date
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        $thedata = $data[0]['data'];
        if ($thedata) {
            return $this->getDateFromTimestamp($thedata);
        } else {
            return null;
        }
    }

    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $result = [];

        $thedata = null;
        if ($data) {
            $thedata = $data->getTimestamp();
        }
        $diffdata = [];
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->fieldtype;
        $diffdata['value'] = $this->getVersionPreview($data, $object, $params);
        $diffdata['data'] = $thedata;
        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;
        $diffdata['disabled'] = false;

        $result[] = $diffdata;

        return $result;
    }
}
