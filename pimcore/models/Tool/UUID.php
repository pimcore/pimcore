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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;

include_once ("UUID.php");

class UUID extends Model\AbstractModel {

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
        $instanceIdentifier = \Pimcore\Config::getSystemConfig()->general->instanceIdentifier;
        if(!$instanceIdentifier){
            throw new \Exception("No instance identier set in system config!");
        }
        $this->setInstanceIdentifier($instanceIdentifier);
        return $this;
    }

    /**
     * @param $id
     * @return $this
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
     * @param $type
     * @return $this
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

    /**
     * @return string
     * @throws \Exception
     */
    public function getUuidResourceName(){
        if(!$this->getType()){
            throw new \Exception("Couldn't create UUID - no 'type' specified.");
        }

        if(!$this->getItemId()){
            throw new \Exception("Couldn't create UUID - no 'itemId' specified.");
        }

        $resourceName =  implode('_',array_filter(array($this->getType(),$this->getItemId())));
        return $resourceName;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function createUuid(){

        if(!$this->getInstanceIdentifier()){
            throw new \Exception("No instance identifier specified.");
        }

        $uuid = \UUID::generate(\UUID::UUID_NAME_SHA1,\UUID::FMT_STRING,$this->getUuidResourceName(),$this->getInstanceIdentifier());
        return $uuid;
    }
    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param $uuid
     */
    public function setUuid($uuid){
        $this->uuid = $uuid;
    }

    /**
     * @param $item
     * @return $this
     */
    public function setItem($item){
        $this->setItemId($item->getId());
        $this->setType(Model\Element\Service::getElementType($item));

        $this->item = $item;
        return $this;
    }

    /**
     * @param $item
     * @return UUID
     * @throws \Exception
     */
    public static function getByItem($item){
        $self = new self;
        $self->setSystemInstanceIdentifier();
        $self->setUuid($self->setItem($item)->createUuid());
        return $self;
    }

    /**
     * @param $uuid
     * @return mixed
     */
    public static function getByUuid($uuid){
        $self = new self;
        return $self->getResource()->getByUuid($uuid);
    }

    /**
     * @param $item
     * @return static
     * @throws \Exception
     */
    public static function create($item){
        $uuid = new static;
        $uuid->setSystemInstanceIdentifier()->setItem($item);
        $uuid->setUuid($uuid->createUuid());
        $uuid->save();
        return $uuid;
    }

}