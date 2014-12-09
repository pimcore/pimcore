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

class StructuredTable extends Model\Object\ClassDefinition\Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "structuredTable";

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
    public $labelWidth;

    /**
     * @var string
     */
    public $labelFirstCell;

    /**
     * @var object
     */
    public $cols;


    /**
     * @var object
     */
    public $rows;


    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = null;

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = null;

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
    public function getLabelWidth() {
        return $this->labelWidth;
    }

    /**
     * @param integer $labelWidth
     * @return void
     */
    public function setLabelWidth($labelWidth) {
        $this->labelWidth = $labelWidth;
        return $this;
    }

    /**
     * @param $labelFirstCell
     * @return $this
     */
    public function setLabelFirstCell($labelFirstCell) {
        $this->labelFirstCell = $labelFirstCell;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelFirstCell() {
        return $this->labelFirstCell;
    }

    /**
     * @return object
     */
    public function getCols() {
        return $this->cols;
    }

    /**
     * @param object $cols
     * @return void
     */
    public function setCols($cols) {
        if(isset($cols['key'])) {
            $cols = array($cols);
        }
        usort($cols, array($this, 'sort'));

        $this->cols = array();;
        foreach($cols as $c) {
            $c['key'] = strtolower($c['key']);
            $this->cols[] = $c;
        }
        return $this;
    }

    /**
     * @return object
     */
    public function getRows() {
        return $this->rows;
    }

    /**
     * @param object $rows
     * @return void
     */
    public function setRows($rows) {
        if(isset($rows['key'])) {
            $rows = array($rows);
        }

        usort($rows, array($this, 'sort'));


        $this->rows = array();;
        foreach($rows as $r) {
            $r['key'] = strtolower($r['key']);
            $this->rows[] = $r;
        }
        return $this;
    }

    public function sort($a, $b) {
        if(is_array($a) && is_array($b)) {
            return $a['position'] - $b['position']; // strcmp($a['position'], $b['position']);
        }
        return strcmp($a, $b);
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        $resourceData = array();
        if(!empty($data)) {
            $data = $data->getData();

            foreach($this->getRows() as $r) {
                foreach($this->getCols() as $c) {
                    $name = $r['key'] . "#" . $c['key'];
                    $resourceData[$this->getName() . "__" . $name] = $data[$r['key']][$c['key']];
                }
            }
        }

        return $resourceData;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @return Object\Data\StructuredTable
     */
    public function getDataFromResource($data) {

        $structuredData = array();
        foreach($this->getRows() as $r) {
            foreach($this->getCols() as $c) {
                $name = $r['key'] . "#" . $c['key'];
                $structuredData[$r['key']][$c['key']] = $data[$this->getName() . "__" . $name];
            }
        }

        return new Object\Data\StructuredTable($structuredData);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        $editArray = array();
        if($data instanceof Object\Data\StructuredTable) {
            if($data->isEmpty()) {
                return array();
            } else {
                $data = $data->getData();
                foreach($this->getRows() as $r) {
                    $editArrayItem = array();
                    $editArrayItem["__row_identifyer"] = $r['key'];
                    $editArrayItem["__row_label"] = $r['label'];
                    foreach($this->getCols() as $c) {
                        $editArrayItem[$c['key']] = $data[$r['key']][$c['key']];
                    }
                    $editArray[] = $editArrayItem;
                }
            }
        }

        return $editArray;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {

        $table = new Object\Data\StructuredTable();
        $tableData = array();
        foreach($data as $dataLine) {
            foreach($this->cols as $c) {
                $tableData[$dataLine['__row_identifyer']][$c['key']] = $dataLine[$c['key']];
            }
        }
        $table->setData($tableData);

        return $table;
    }

    /**
     * @param $data
     * @param null $object
     * @return array|null
     */
    public function getDataForGrid($data, $object = null) {
        if($data instanceof Object\Data\StructuredTable) {
            if(!$data->isEmpty()) {
                return $data->getData();
            }
        }
        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data) {
            return $data->getHtmlTable($this->rows, $this->cols);
        } else {
            return null;
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){
        if(!$omitMandatoryCheck and $this->getMandatory()){

            $empty = true;
            if(!empty($data)) {
                $dataArray = $data->getData();
                foreach($this->getRows() as $r) {
                    foreach($this->getCols() as $c) {
                        if(!empty($dataArray[$r['key']][$c['key']])) {
                            $empty = false;
                        }
                    }
                }
            }
            if($empty) {
                throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
            }
        }

       if(!empty($data) and !$data instanceof Object\Data\StructuredTable){
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
        $value = $this->getDataFromObjectParam($object);

        if ($value instanceof Object\Data\StructuredTable) {
            $string = "";
            $dataArray = $value->getData();
            foreach($this->getRows() as $r) {
                foreach($this->getCols() as $c) {
                    $string .= $dataArray[$r['key']][$c['key']] . "##";
                }
            }
            return $string;
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @return mixed|Object\Data\StructuredTable
     */
    public function getFromCsvImport($importValue) {
        $dataArray = explode("##", $importValue);

        $i = 0;
        $dataTable = array();
        foreach($this->getRows() as $r) {
            foreach($this->getCols() as $c) {
                $dataTable[$r['key']][$c['key']] = $dataArray[$i];
                $i++;
            }
        }

        $value = new Object\Data\StructuredTable($dataTable);
        return $value;
    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport ($object) {

        $webserviceArray = array();
        $table = $this->getDataFromObjectParam($object);

        if ($table instanceof Object\Data\StructuredTable) {

            $dataArray = $table->getData();
            foreach($this->getRows() as $r) {
                foreach($this->getCols() as $c) {
                    $name = $r['key'] . "#" . $c['key'];
                    $webserviceArray[$name] = $dataArray[$r['key']][$c['key']];
                }
            }

            return $webserviceArray;
        } else return null;
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null) {
        if(empty($value)){
            return null;
        } else {
            if ($value instanceof \stdClass) {
                $value = (array) $value;
            }
            if(is_array($value)){
                $dataArray = array();
                foreach($this->getRows() as $r) {
                    foreach($this->getCols() as $c) {
                        $name = $r['key'] . "#" . $c['key'];
                        $dataArray[$r['key']][$c['key']] = $value[$name];
                    }
                }

                return new Object\Data\StructuredTable($dataArray);
            } else {
                throw new \Exception("cannot get values from web service import - invalid data");
            }
        }
    }

    /**
     * @return array|string
     */
    public function getColumnType() {
        $columns = array();
        foreach($this->calculateDbColumns() as $c) {
            $columns[$c->name] = $c->type;
        }
        return $columns;
    }

    /**
     * @return array|string
     */
    public function getQueryColumnType() {
        $columns = array();
        foreach($this->calculateDbColumns() as $c) {
            $columns[$c->name] = $c->type;
        }
        return $columns;
    }

    /**
     * @return array
     */
    protected function calculateDbColumns() {

        $rows = $this->getRows();
        $cols = $this->getCols();

        $dbCols = array();

        foreach($rows as $r) {
            foreach($cols as $c) {
                $name = $r['key'] . "#" . $c['key'];

                $col = new \stdClass();
                $col->name = $name;
                $col->type = $this->typeMapper($c['type']);
                $dbCols[] = $col;
            }
        }

        return $dbCols;
    }

    /**
     * @param $type
     * @return mixed
     */
    protected function typeMapper($type) {
        $mapper = array(
            "text" => "varchar(255)",
            "number" => "double",
            "bool" => "tinyint(1)"
        );

        return $mapper[$type];
    }


    /**
     * @param $data
     * @return bool
     */
    public function isEmpty($data) {
        if($data instanceof Object\Data\StructuredTable) {
            return $data->isEmpty();
        } else {
            return true;
        }
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }

    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null) {
        $defaultData = parent::getDiffDataForEditMode($data, $object);
        $html =  $defaultData[0]["value"];
        $value = array();
        $value["html"] = $html;
        $value["type"] = "html";
        $defaultData[0]["value"] = $value;
        return $defaultData;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        $this->labelWidth = $masterDefinition->labelWidth;
        $this->labelFirstCell = $masterDefinition->labelFirstCell;
        $this->cols = $masterDefinition->cols;
        $this->rows = $masterDefinition->rows;
    }

}
