<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;

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
            throw new \Exception("No instance identifier set in system config!");
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
     * @return mixed
     * @throws \Exception
     */
    public function createUuid(){

        if(!$this->getInstanceIdentifier()){
            throw new \Exception("No instance identifier specified.");
        }

        $uuid = \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_DNS, $this->getInstanceIdentifier());

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
        return $self->getDao()->getByUuid($uuid);
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