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
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Element_Note extends Pimcore_Model_Abstract {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var int
     */
    public $date;

    /**
     * @var int
     */
    public $user;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $description;

    /**
     * @var array
     */
    public $data = array();

    /**
     * @static
     * @param $id
     * @return Element_Note
     */
    public static function getById ($id) {

        try {
            $note = new self();
            $note->getResource()->getById($id);

            return $note;
        } catch (Exception $e) {
            return null;
        }
    }


    /**
     * @param string $name
     * @param string $type
     * @param mixed $data
     */
    public function addData($name, $type, $data) {
        $this->data[$name] = array(
            "type" => $type,
            "data" => $data
        );
    }

    /**
     * @param Element_Interface $element
     */
    public function setElement(Element_Interface $element) {
        $this->setCid($element->getId());
        $this->setCtype(Element_Service::getType($element));
    }

    public function save() {

        // check if there's a valid user
        if(!$this->getUser()) {
            // try to use the logged in user
            if(Pimcore::inAdmin()) {
                if($user = Pimcore_Tool_Admin::getCurrentUser()) {
                    $this->setUser($user->getId());
                }
            }
        }

        $this->getResource()->save();
    }

    /**
     * @param int $cid
     */
    public function setCid($cid)
    {
        $this->cid = (int) $cid;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @param string $ctype
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;
    }

    /**
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = (int) $date;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }
}