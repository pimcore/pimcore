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

class Object_Class_Data_Slider extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "slider";

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * @var float
     */
    public $minValue;

    /**
     * @var float
     */
    public $maxValue;

    /**
     * @var boolean
     */
    public $vertical;

    /**
     * @var float
     */
    public $increment;
    
    /**
     * @var int
     */
    public $decimalPrecision;
    
    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "double";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "double";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "float";

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
     * @return float
     */
    public function getMinValue() {
        return $this->minValue;
    }

    /**
     * @param float $minValue
     * @return void
     */
    public function setMinValue($minValue) {
        $this->minValue = $this->getAsFloatCast($minValue);
    }

    /**
     * @return float
     */
    public function getMaxValue() {
        return $this->maxValue;
    }

    /**
     * @param float $minValue
     * @return void
     */
    public function setMaxValue($maxValue) {
        $this->maxValue = $this->getAsFloatCast($maxValue);
    }

    /**
     * @return boolean
     */
    public function getVertical() {
        return $this->vertical;
    }

    /**
     * @return integer
     */
    public function getDefaultValue() {
        return null;
    }


    /**
     * @param boolean $vertical
     * @return void
     */
    public function setVertical($vertical) {
        $this->vertical = (bool) $vertical;
    }

    /**
     * @return float
     */
    public function getIncrement() {
        return $this->increment;
    }

    /**
     * @param float $increment
     * @return void
     */
    public function setIncrement($increment) {
        $this->increment = $this->getAsFloatCast($increment);
    }
    
    
    /**
     * @return int
     */
    public function getDecimalPrecision() {
        return $this->decimalPrecision;
    }

    /**
     * @param int $decimalPrecision
     * @return void
     */
    public function setDecimalPrecision($decimalPrecision) {
        $this->decimalPrecision = $this->getAsIntegerCast($decimalPrecision);
    }

    /**
     * @see Object_Class_Data::getDataForResource
     * @param float $data
     * @param null|Object_Abstract $object
     * @return float
     */
    public function getDataForResource($data, $object = null) {
        return (float) $data;
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param float $data
     * @return float
     */
    public function getDataFromResource($data) {
        return (float) $data;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param float $data
     * @param null|Object_Abstract $object
     * @return float
     */
    public function getDataForQueryResource($data, $object = null) {
        return $data;
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param float $data
     * @param null|Object_Abstract $object
     * @return float
     */
    public function getDataForEditmode($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param float $data
     * @param null|Object_Abstract $object
     * @return float
     */
    public function getDataFromEditmode($data, $object = null) {
        return $this->getDataFromResource($data);
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param float $data
     * @return float
     */
    public function getVersionPreview($data) {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and $data === NULL){
            throw new Exception("Empty mandatory field [ ".$this->getName()." ] ".strval($data));
        }

        if(!empty($data) and !is_numeric($data)){
            throw new Exception("invalid slider data");
        }
    }




}
