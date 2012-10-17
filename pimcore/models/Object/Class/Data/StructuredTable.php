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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_StructuredTable extends Object_Class_Data {

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
    }

    /**
     * @param string $labelFirstCell
     */
    public function setLabelFirstCell($labelFirstCell) {
        $this->labelFirstCell = $labelFirstCell;
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

    }

    public function sort($a, $b) {
        if(is_array($a) && is_array($b)) {
            return $a['position'] - $b['position']; // strcmp($a['position'], $b['position']);
        }
        return strcmp($a, $b);
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param string $data
     * @param null|Object_Abstract $object
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
     * @see Object_Class_Data::getDataFromResource
     * @param string $data
     * @return Object_Data_StructuredTable
     */
    public function getDataFromResource($data) {

        $structuredData = array();
        foreach($this->getRows() as $r) {
            foreach($this->getCols() as $c) {
                $name = $r['key'] . "#" . $c['key'];
                $structuredData[$r['key']][$c['key']] = $data[$this->getName() . "__" . $name];
            }
        }

        return new Object_Data_StructuredTable($structuredData);
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        $editArray = array();
        if($data instanceof Object_Data_StructuredTable) {
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
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {

        $table = new Object_Data_StructuredTable();
        $tableData = array();
        foreach($data as $dataLine) {
            foreach($this->cols as $c) {
                $tableData[$dataLine['__row_identifyer']][$c['key']] = $dataLine[$c['key']];
            }
        }
        $table->setData($tableData);

        return $table;
    }

    public function getDataForGrid($data, $object = null) {
        if($data instanceof Object_Data_StructuredTable) {
            if(!$data->isEmpty()) {
                return $data->getData();
            }
        }
        return null;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        return $data->getHtmlTable($this->rows, $this->cols);
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
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
                throw new Exception("Empty mandatory field [ ".$this->getName()." ]");
            }
        }

       if(!empty($data) and !$data instanceof Object_Data_StructuredTable){
            throw new Exception("invalid table data");
       }
    }

     /**
      * converts object data to a simple string value or CSV Export
      * @abstract
      * @param Object_Abstract $object
      * @return string
      */
    public function getForCsvExport($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);
        $value = $object->$getter();
        if ($value instanceof Object_Data_StructuredTable) {
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
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
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

        $value = new Object_Data_StructuredTable($dataTable);
        return $value;
    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport ($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);

        $webserviceArray = array();

        $table = $object->$getter();
        if ($table instanceof Object_Data_StructuredTable) {

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
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport ($value) {
        if(empty($value)){
            return null;
        } else if(is_array($value)){
            $dataArray = array();
            foreach($this->getRows() as $r) {
                foreach($this->getCols() as $c) {
                    $name = $r['key'] . "#" . $c['key'];
                    $dataArray[$r['key']][$c['key']] = $value[$name];
                }
            }

            return new Object_Data_StructuredTable($dataArray);
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
    }

    public function getColumnType() {
        $columns = array();
        foreach($this->calculateDbColumns() as $c) {
            $columns[$c->name] = $c->type;
        }
        return $columns;
    }

    public function getQueryColumnType() {
        $columns = array();
        foreach($this->calculateDbColumns() as $c) {
            $columns[$c->name] = $c->type;
        }
        return $columns;
    }


    protected function calculateDbColumns() {

        $rows = $this->getRows();
        $cols = $this->getCols();

        $dbCols = array();

        foreach($rows as $r) {
            foreach($cols as $c) {
                $name = $r['key'] . "#" . $c['key'];

                $col = new stdClass();
                $col->name = $name;
                $col->type = $this->typeMapper($c['type']);
                $dbCols[] = $col;
            }
        }

        return $dbCols;
    }

    protected function typeMapper($type) {
        $mapper = array(
            "text" => "varchar(50)",
            "number" => "double",
            "bool" => "tinyint(1)"
        );

        return $mapper[$type];
    }



    public function getGetterCode ($class) {
        // getter

        $key = $this->getName();
        $code = "";

        $code .= '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}' . "\n";

        if(method_exists($this,"preGetData")) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        // insert this line if inheritance from parent objects is allowed
        if ($class->getAllowInherit()) {
            $code .= "\t" . 'if((!$data || $data->isEmpty()) && Object_Abstract::doGetInheritedValues()) { ' . "\n";
            $code .= "\t\t" . '$parentData  = $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t\t" . 'if($parentData) { return $parentData; }' . "\n";
            $code .= "\t\t" . 'else { return $data; }' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    
    /**
     * Creates getter code which is used for generation of php file for object brick classes using this data type
     * @param $brickClass
     * @return string
     */
    public function getGetterCodeObjectbrick ($brickClass) {
        $key = $this->getName();
        $code = '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        $code .= "\t" . 'if((!$this->' . $key . '|| $this->' . $key . '->isEmpty()) && Object_Abstract::doGetInheritedValues($this->getObject())) {' . "\n";
        $code .= "\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
        $code .= "\t" . '}' . "\n";

        $code .= "\t return " . '$this->' . $key . ";\n";
        $code .= "}\n\n";

        return $code;

    }

}
