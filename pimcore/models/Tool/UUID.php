<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 05.06.13
 * Time: 18:42
 */


include_once ("UUID.php");

class Tool_UUID extends Pimcore_Model_Abstract {

    public $itemId;
    public $type;
    public $uuid;
    public $instanceIdentifier;
    protected $item;

    public function setInstanceIdentifier($instanceIdentifier){
        $this->instanceIdentifier = $instanceIdentifier;
        return $this;
    }

    public function getInstanceIdentifier(){
        return $this->instanceIdentifier;
    }

    public function setSystemInstanceIdentifier(){
        $instanceIdentifier = Pimcore_Config::getSystemConfig()->general->instanceIdentifier;
        if(!$instanceIdentifier){
            throw new Exception("No instance identier set in system config!");
        }
        $this->setInstanceIdentifier($instanceIdentifier);
        return $this;
    }

    /**
     * @param mixed $id
     */
    public function setItemId($id)
    {
        $this->itemId = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function getUuidResourceName(){
        if(!$this->getType()){
            throw new Exception("Couldn't create UUID - no 'type' specified.");
        }

        if(!$this->getItemId()){
            throw new Exception("Couldn't create UUID - no 'itemId' specified.");
        }

        $resourceName =  implode('_',array_filter(array($this->getType(),$this->getItemId())));
        return $resourceName;
    }

    public function createUuid(){

        if(!$this->getInstanceIdentifier()){
            throw new Exception("No instance identifier specified.");
        }

        $uuid = UUID::generate(UUID::UUID_NAME_SHA1,UUID::FMT_STRING,$this->getUuidResourceName(),$this->getInstanceIdentifier());
        return $uuid;
    }
    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid){
        $this->uuid = $uuid;
    }

    public function setItem($item){
        $this->setItemId($item->getId());
        $this->setType(Element_Service::getElementType($item));

        $this->item = $item;
        return $this;
    }


    public static function getByItem($item){
        $self = new self;
        $self->setSystemInstanceIdentifier();
        $self->setUuid($self->setItem($item)->createUuid());
        return $self;
    }

    public static function getByUuid($uuid){
        $self = new self;
        return $self->getResource()->getByUuid($uuid);
    }

    public static function create($item){
        $uuid = new static;
        $uuid->setSystemInstanceIdentifier()->setItem($item);
        $uuid->setUuid($uuid->createUuid());
        $uuid->save();
        return $uuid;
    }

}