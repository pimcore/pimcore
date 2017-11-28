<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;
use Pimcore\Event\ElementEvents;
use Pimcore\Event\Model\ElementEvent;
use Symfony\Component\CssSelector\Node\ElementNode;

/**
 * @method \Pimcore\Model\Element\Note\Dao getDao()
 */
class Note extends Model\AbstractModel
{
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
    public $data = [];

    /**
     * @static
     *
     * @param $id
     *
     * @return Note
     */
    public static function getById($id)
    {
        try {
            $note = new self();
            $note->getDao()->getById($id);

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
    public function addData($name, $type, $data)
    {
        $this->data[$name] = [
            'type' => $type,
            'data' => $data
        ];
    }

    /**
     * @param ElementInterface $element
     *
     * @return $this
     */
    public function setElement(ElementInterface $element)
    {
        $this->setCid($element->getId());
        $this->setCtype(Service::getType($element));

        return $this;
    }

    public function save()
    {

        // check if there's a valid user
        if (!$this->getUser()) {
            // try to use the logged in user
            if (\Pimcore::inAdmin()) {
                if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
                    $this->setUser($user->getId());
                }
            }
        }

        $isUpdate = $this->getId() ? true : false;
        $this->getDao()->save();

        if(!$isUpdate){
            \Pimcore::getEventDispatcher()->dispatch(ElementEvents::POST_ADD, new ElementEvent($this));
        }
    }

    /**
     * @param $cid
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
