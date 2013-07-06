<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 19.05.13
 * Time: 21:43
 */
class Deployment_Classes_Phing_Param {
    protected $value;
    protected $name;

    public function setname($name) {
        $this->name = $name;
    }

    public function getname(){
        return $this->name;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function addText($text) {
        $this->setValue($text);
    }

    public function getValue() {
        return $this->value;
    }

    public function __toString(){
        return $this->getValue();
    }
}