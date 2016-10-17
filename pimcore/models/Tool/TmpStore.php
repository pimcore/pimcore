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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\TmpStore\Dao getDao()
 */
class TmpStore extends Model\AbstractModel
{

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $tag;

    /**
     * @var string
     */
    public $data;

    /**
     * @var int
     */
    public $date;

    /**
     * @var int
     */
    public $expiryDate;

    /**
     * @var bool
     */
    public $serialized = false;

    /**
     * @var Lock
     */
    protected static $instance;

    /**
     * @return Lock
     */
    protected static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param $id
     * @param $data
     * @param null $tag
     * @param null $lifetime
     * @return mixed
     */
    public static function add($id, $data, $tag = null, $lifetime = null)
    {
        $instance = self::getInstance();

        if (!$lifetime) {
            $lifetime = 86400;
        }

        if (self::get($id)) {
            return true;
        }

        return $instance->getDao()->add($id, $data, $tag, $lifetime);
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function delete($id)
    {
        $instance = self::getInstance();

        return $instance->getDao()->delete($id);
    }

    /**
     * @param $id
     * @return null|TmpStore
     */
    public static function get($id)
    {
        $item = new self;
        if ($item->getById($id)) {
            if ($item->getExpiryDate() < time()) {
                self::delete($id);
            } else {
                return $item;
            }
        }

        return null;
    }

    /**
     *
     */
    public static function cleanup()
    {
        $instance = self::getInstance();
        $instance->getDao()->cleanup();
    }

    /**
     * @param $tag
     * @return array
     */
    public static function getIdsByTag($tag)
    {
        $instance = self::getInstance();
        $items = $instance->getDao()->getIdsByTag($tag);

        return $items;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return boolean
     */
    public function isSerialized()
    {
        return $this->serialized;
    }

    /**
     * @param boolean $serialized
     */
    public function setSerialized($serialized)
    {
        $this->serialized = $serialized;
    }

    /**
     * @return int
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * @param int $expiryDate
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @param null $lifetime
     * @return mixed
     */
    public function update($lifetime = null)
    {
        if (!$lifetime) {
            $lifetime = 86400;
        }

        return $this->getDao()->add($this->getId(), $this->getData(), $this->getTag(), $lifetime);
    }
}
