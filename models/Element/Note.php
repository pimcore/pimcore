<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Element;

use Pimcore\Event\ElementEvents;
use Pimcore\Event\Model\ElementEvent;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Element\Note\Dao getDao()
 */
final class Note extends Model\AbstractModel
{
    /**
     * @internal
     *
     * @var int|null
     */
    protected $id;

    /**
     * @internal
     *
     * @var string
     */
    protected $type;

    /**
     * @internal
     *
     * @var int
     */
    protected $cid;

    /**
     * @internal
     *
     * @var string
     */
    protected $ctype;

    /**
     * @internal
     *
     * @var int
     */
    protected $date;

    /**
     * @internal
     *
     * @var int|null
     */
    protected $user;

    /**
     * @internal
     *
     * @var string
     */
    protected $title;

    /**
     * @internal
     *
     * @var string
     */
    protected $description;

    /**
     * @internal
     *
     * @var array
     */
    protected $data = [];

    /**
     * @static
     *
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $note = new self();
            $note->getDao()->getById($id);

            return $note;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $data
     *
     * @return $this
     */
    public function addData($name, $type, $data)
    {
        $this->data[$name] = [
            'type' => $type,
            'data' => $data,
        ];

        return $this;
    }

    /**
     * @param ElementInterface $element
     *
     * @return $this
     */
    public function setElement(ElementInterface $element)
    {
        $this->setCid($element->getId());
        $this->setCtype(Service::getElementType($element));

        return $this;
    }

    /**
     * @throws \Exception
     */
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

        if (!$isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new ElementEvent($this), ElementEvents::POST_ADD);
        }
    }

    /**
     * @param int $cid
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
     * @param string $ctype
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
     * @param array $data
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
     * @param int $date
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
     * @param string $description
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
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
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
     * @param string $type
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
     * @param int $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
