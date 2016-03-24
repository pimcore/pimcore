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
use Pimcore\Tool\Serialize;
use Pimcore\Model\Object;

class Table extends Model\Object\ClassDefinition\Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "table";

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * @var integer
     */
    public $cols;


    /**
     * @var integer
     */
    public $rows;

    /**
     * Default data
     * @var integer
     */
    public $data;


    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "longtext";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "longtext";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "array";

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);
        return $this;
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);
        return $this;
    }

    /**
     * @return integer
     */
    public function getCols()
    {
        return $this->cols;
    }

    /**
     * @param integer $cols
     * @return void
     */
    public function setCols($cols)
    {
        $this->cols = $this->getAsIntegerCast($cols);
        return $this;
    }

    /**
     * @return integer
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param integer $rows
     * @return void
     */
    public function setRows($rows)
    {
        $this->rows = $this->getAsIntegerCast($rows);
        return $this;
    }


    /**
     * @return integer
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param integer $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = array())
    {
        return Serialize::serialize($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = array())
    {
        return Serialize::unserialize((string) $data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = array())
    {
        if (!empty($data)) {
            $tmpLine = array();
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $tmpLine[] = implode("|", $row);
                    }
                }
            }
            return implode("\n", $tmpLine);
        }
        return "";
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = array())
    {
        return $data;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = array())
    {
        
        // check for empty data
        $checkData = "";
        if (is_array($data)) {
            foreach ($data as $row) {
                if (is_array($row)) {
                    $checkData .= implode("", $row);
                }
            }
        }
        $checkData = str_replace(" ", "", $checkData);
        
        if (empty($checkData)) {
            return null;
        }
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = array())
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
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (!empty($data) and !is_array($data)) {
            throw new \Exception("invalid table data");
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = array())
    {
        $data = $this->getDataFromObjectParam($object);
        if (is_array($data)) {
            return base64_encode(Serialize::serialize($data));
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed|null
     */
    public function getFromCsvImport($importValue, $object = null, $params = array())
    {
        $value = Serialize::unserialize(base64_decode($importValue));
        if (is_array($value)) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param $object
     * @param mixed $params
     * @return string
     */
    public function getDataForSearchIndex($object, $params = array())
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if (!empty($data)) {
            $tmpLine = array();
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $tmpLine[] = implode(" ", $row);
                    }
                }
            }
            return implode("\n", $tmpLine);
        }
        return "";
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = array())
    {
        return true;
    }


    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = array())
    {
        if ($data) {
            $html = "<table>";

            foreach ($data as $row) {
                $html .= "<tr>";

                if (is_array($row)) {
                    foreach ($row as $cell) {
                        $html .= "<td>";
                        $html .= $cell;
                        $html .= "</th>";
                    }
                }
                $html .= "</tr>";
            }
            $html .= "</table>";

            $value = array();
            $value["html"] = $html;
            $value["type"] = "html";
            return $value;
        } else {
            return "";
        }
    }


    /** converts data to be imported via webservices
     * @param mixed $value
     * @param null $object
     * @param mixed $params
     * @return array|mixed
     */
    public function getFromWebserviceImport($value, $object = null, $params = array(), $idMapper = null)
    {
        if ($value && is_array($value)) {
            $result = array();
            foreach ($value as $item) {
                $item = (array) $item;
                $item = array_values($item);
                $result[] = $item;
            }

            return $result;
        }

        return $value;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->cols = $masterDefinition->cols;
        $this->rows = $masterDefinition->rows;
        $this->data = $masterDefinition->data;
    }
}
