<?php

class KeyValue_KeyConfig extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    public $description;

    public $type;

    public $unit;
#
    public $group;

    public $possiblevalues;


    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function setPossibleValues($values)
    {
        $this->possiblevalues = $values;
    }

    public function getPossibleValues()
    {
        return $this->possiblevalues;
    }



    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setGroup($group)
    {
        $this->group = $group;
    }

    public function getGroup()
    {
        return $this->group;
    }


    /**
     * @param integer $id
     * @return KeyValue_KeyConfig
     */
    public static function getById($id) {

        $config = new self();
        $config->setId(intval($id));
        $config->getResource()->getById();

        return $config;
    }


    public static function getByName ($name) {
        $config = new self();
        $config->setName($name);
        $config->getResource()->getByName();

        return $config;
    }


    /**
     * @return KeyValue_KeyConfig
     */
    public static function create() {
        $config = new self();
        $config->save();

        return $config;
    }


    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }
}
