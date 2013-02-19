<?php

class Object_KeyValue_KeyConfig extends Pimcore_Model_Abstract {

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

    public $group;

    public $possiblevalues;


    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function setPossibleValues($values)
    {
        $this->possiblevalues = $values;
        return $this;
    }

    public function getPossibleValues()
    {
        return $this->possiblevalues;
    }



    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    public function getGroup()
    {
        return $this->group;
    }


    /**
     * @param integer $id
     * @return Object_KeyValue_KeyConfig
     */
    public static function getById($id) {
        try {

            $config = new self();
            $config->setId(intval($id));
            $config->getResource()->getById();

            return $config;
        } catch (Exception $e) {

        }
    }


    public static function getByName ($name, $groupId = null) {
        try {
            $config = new self();
            $config->setName($name);
            $config->setGroup($groupId);
            $config->getResource()->getByName();

            return $config;
        } catch (Exception $e) {

        }
    }


    /**
     * @return Object_KeyValue_KeyConfig
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
        return $this;
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
        return $this;
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
        return $this;
    }
}
