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
use Pimcore\Tool\Serialize;
use Pimcore\Model\Object;

class Table extends Model\Object\ClassDefinition\Data {

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
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width) {
        $this->width = $this->getAsIntegerCast($width);
        return $this;
    }

    /**
     * @return integer
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
        return $this;
    }

    /**
     * @return integer
     */
    public function getCols() {
        return $this->cols;
    }

    /**
     * @param integer $cols
     * @return void
     */
    public function setCols($cols) {
        $this->cols = $this->getAsIntegerCast($cols);
        return $this;
    }

    /**
     * @return integer
     */
    public function getRows() {
        return $this->rows;
    }

    /**
     * @param integer $rows
     * @return void
     */
    public function setRows($rows) {
        $this->rows = $this->getAsIntegerCast($rows);
        return $this;
    }


    /**
     * @return integer
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param integer $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        return Serialize::serialize($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {
        return Serialize::unserialize((string) $data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {

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
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        return $data;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {
        
        // check for empty data
        $checkData = "";
        if (is_array($data)) {
            foreach ($data as $row) {
                if (is_array($row)) {
                    $checkData .= implode("", $row);
                }
            }
        }
        $checkData = str_replace(" ","",$checkData);
        
        if(empty($checkData)) {
            return null;
        }
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

       if(!empty($data) and !is_array($data)){
            throw new \Exception("invalid table data");
       }
    }

     /**
      * converts object data to a simple string value or CSV Export
      * @abstract
      * @param Model\Object\AbstractObject $object
      * @return string
      */
    public function getForCsvExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if (is_array($data)) {
            return base64_encode(Serialize::serialize($data));
        } else return null;

    }

    /**
     * @param $importValue
     * @return mixed|null
     */
    public function getFromCsvImport($importValue) {

       $value = Serialize::unserialize(base64_decode($importValue));
        if (is_array($value)) {
            return $value;
        } else return null;

    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }


    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null) {
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
     * @return array|mixed
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null)
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
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        $this->cols = $masterDefinition->cols;
        $this->rows = $masterDefinition->rows;
        $this->data = $masterDefinition->data;
    }
}
