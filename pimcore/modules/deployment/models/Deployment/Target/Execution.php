<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 27.05.13
 * Time: 22:09
 */

class Deployment_Target_Execution extends Pimcore_Model_Abstract {

    const STATUS_START = 'started';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    public $id;
    public $parentId;
    public $name;
    public $creationDate;
    public $status;

    protected $parent;

    /**
     * @param mixed $parent
     */
    public function setParent(Deployment_Target_Execution $parent)
    {
        $this->parent = $parent;
        $this->setParentId($parent->getId());
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }



    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }





}