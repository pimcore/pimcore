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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;

/**
 * @method bool getById(string $id)
 * @method \Pimcore\Model\Tool\TmpStore\Dao getDao()
 */
final class TmpStore extends Model\AbstractModel
{
    /**
     * @internal
     *
     * @var string
     */
    protected $id;

    /**
     * @internal
     *
     * @var string
     */
    protected $tag;

    /**
     * @internal
     *
     * @var mixed
     */
    protected $data;

    /**
     * @internal
     *
     * @var int
     */
    protected $date;

    /**
     * @internal
     *
     * @var int
     */
    protected $expiryDate;

    /**
     * @internal
     *
     * @var bool
     */
    protected $serialized = false;

    /**
     * @internal
     *
     * @var self|null
     */
    protected static ?self $instance = null;

    /**
     * @return self
     */
    private static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return int
     */
    private static function getDefaultLifetime()
    {
        return 86400 * 7;
    }

    /**
     * @param string $id
     * @param mixed $data
     * @param string|null $tag
     * @param int|null $lifetime
     *
     * @return bool
     */
    public static function add($id, $data, $tag = null, $lifetime = null)
    {
        $instance = self::getInstance();

        if (!$lifetime) {
            $lifetime = self::getDefaultLifetime();
        }

        if (self::get($id)) {
            return true;
        }

        return $instance->getDao()->add($id, $data, $tag, $lifetime);
    }

    /**
     * @param string $id
     * @param mixed $data
     * @param string|null $tag
     * @param int|null $lifetime
     *
     * @return bool
     */
    public static function set($id, $data, $tag = null, $lifetime = null)
    {
        $instance = self::getInstance();

        if (!$lifetime) {
            $lifetime = self::getDefaultLifetime();
        }

        return $instance->getDao()->add($id, $data, $tag, $lifetime);
    }

    /**
     * @param string $id
     *
     * @return void
     */
    public static function delete($id)
    {
        $instance = self::getInstance();
        $instance->getDao()->delete($id);
    }

    /**
     * @param string $id
     *
     * @return null|TmpStore
     */
    public static function get($id)
    {
        $item = new self();
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
     * @param string $tag
     *
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
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
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
     * @return bool
     */
    public function isSerialized()
    {
        return $this->serialized;
    }

    /**
     * @param bool $serialized
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
     * @param int|null $lifetime
     *
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
