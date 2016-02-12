<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;

class Datetime extends Model\Object\ClassDefinition\Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "datetime";

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "bigint(20)";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "bigint(20)";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "\\Pimcore\\Date";


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
     * @param \Zend_Date|\DateTime $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer
     */
    public function getDataForResource($data, $object = null)
    {
        if ($data) {
            return $data->getTimestamp();
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param integer $data
     * @return \Zend_Date|\DateTime
     */
    public function getDataFromResource($data)
    {
        if ($data) {
            return $this->getDateFromTimestamp($data);
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param \Zend_Date|\DateTime $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer
     */
    public function getDataForQueryResource($data, $object = null)
    {
        if ($data) {
            return $data->getTimestamp();
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param \Zend_Date|\DateTime $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null)
    {
        if ($data) {
            return $data->getTimestamp();
        }
    }

    /**
     * @param $timestamp
     * @return \DateTime|\Pimcore\Date
     */
    protected function getDateFromTimestamp($timestamp) {
        if (\Pimcore\Config::getFlag("useZendDate")) {
            $date = new \Pimcore\Date($timestamp);
        } else {
            $date = new \DateTime();
            $date->setTimestamp($timestamp);
        }

        return $date;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param integer $data
     * @param null|Model\Object\AbstractObject $object
     * @return \Zend_Date|\DateTime
     */
    public function getDataFromEditmode($data, $object = null)
    {
        if ($data) {
            return $this->getDateFromTimestamp($data / 1000);
        }
        return false;
    }

    /**
     * @param \Zend_Date|\DateTime $data
     * @param null $object
     * @return null
     */
    public function getDataForGrid($data, $object = null)
    {
        if ($data) {
            return $data->getTimestamp();
        } else {
            return null;
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param \Zend_Date|\DateTime $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        if ($data instanceof \Zend_Date) {
            return $data->toString("Y-m-d H:i", "php");
        } elseif ($data instanceof \DateTime) {
            return $data->format("Y-m-d H:i");
        }
    }


    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Model\Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = array())
    {
        $data = $this->getDataFromObjectParam($object);
        if ($data instanceof \Zend_Date) {
            return $data->toString("Y-m-d H:i", "php");
        } elseif ($data instanceof \DateTime) {
            return $data->format("Y-m-d H:i");
        }
        return null;
    }

    /**
     * @param string $importValue
     * @return null|Date|Model\Object\ClassDefinition\Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue)
    {
        $timestamp = strtotime($importValue);
        if($timestamp) {
            return $this->getDateFromTimestamp($timestamp);
        }

        return null;
    }


    /**
     * converts data to be exposed via webservices
     * @param Object\Concrete $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        return $this->getForCsvExport($object);
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null)
    {
        $timestamp = strtotime($value);
        if (empty($value)) {
            return null;
        } elseif ($timestamp !== false) {
            return $this->getDateFromTimestamp($timestamp);
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }

    /**
     * @return Date
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return $this->defaultValue;
            //return new Date($this->defaultValue);
        } else {
            return 0;
        }
    }

    /**
     * @param mixed $defaultValue
     * @return void
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
     * @param $useCurrentDate
     * @return $this
     */
    public function setUseCurrentDate($useCurrentDate)
    {
        $this->useCurrentDate = (bool)$useCurrentDate;
        return $this;
    }


    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed()
    {
        return true;
    }

    /** See parent class.
     * @param $data
     * @param null $object
     * @return null|Date
     */

    public function getDiffDataFromEditmode($data, $object = null)
    {
        $thedata = $data[0]["data"];
        if ($thedata) {
            return $this->getDateFromTimestamp($thedata);
        } else {
            return null;
        }
    }

    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null)
    {
        $result = array();

        $thedata = null;
        if ($data) {
            $thedata = $data->getTimestamp();
        };
        $diffdata = array();
        $diffdata["field"] = $this->getName();
        $diffdata["key"] = $this->getName();
        $diffdata["type"] = $this->fieldtype;
        $diffdata["value"] = $this->getVersionPreview($data);
        $diffdata["data"] = $thedata;
        $diffdata["title"] = !empty($this->title) ? $this->title : $this->name;
        $diffdata["disabled"] = false;

        $result[] = $diffdata;

        return $result;
    }
}
