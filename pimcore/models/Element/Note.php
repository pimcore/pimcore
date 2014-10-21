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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

class Note extends Model\AbstractModel {

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
     * @return Element\Note
     */
    public static function getById ($id) {

        try {
            $note = new self();
            $note->getResource()->getById($id);

            return $note;
        } catch (\Exception $e) {
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
     * @param ElementInterface $element
     * @return $this
     */
    public function setElement(ElementInterface $element) {
        $this->setCid($element->getId());
        $this->setCtype(Service::getType($element));
        return $this;
    }

    public function save() {

        // check if there's a valid user
        if(!$this->getUser()) {
            // try to use the logged in user
            if(\Pimcore::inAdmin()) {
                if($user = \Pimcore\Tool\Admin::getCurrentUser()) {
                    $this->setUser($user->getId());
                }
            }
        }

        $this->getResource()->save();
    }

    /**
     * @param $cid
     * @return $this
     */
    public function setCid($cid)
    {
        $this->cid = (int) $cid;
        return $this;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @param $ctype
     * @return $this
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;
        return $this;
    }

    /**
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = (int) $date;
        return $this;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }
}