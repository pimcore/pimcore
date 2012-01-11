<?php 

class Object_Class_Data_IndexFieldSelectionField extends Object_Class_Data_Textarea {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "indexFieldSelectionField";


    public $specificPriceField = false;

    public function setSpecificPriceField($specificPriceField) {
        $this->specificPriceField = $specificPriceField;
    }

    public function getSpecificPriceField() {
        return $this->specificPriceField;
    }
}
