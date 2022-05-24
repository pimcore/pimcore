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
    public static function add(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null)
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
    public static function set(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null)
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
    public static function delete(string $id)
    {
        $instance = self::getInstance();
        $instance->getDao()->delete($id);
    }

    /**
     * @param string $id
     *
     * @return null|TmpStore
     */
    public static function get(string $id)
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
    public static function getIdsByTag(string $tag)
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
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string|null $tag
     */
    public function setTag(?string $tag)
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
    public function setData(mixed $data)
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
    public function setDate(int $date)
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
    public function setSerialized(bool $serialized)
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
    public function setExpiryDate(int $expiryDate)
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @param int|null $lifetime
     *
     * @return bool
     */
    public function update(?int $lifetime = null)
    {
        if (!$lifetime) {
            $lifetime = 86400;
        }

        return $this->getDao()->add($this->getId(), $this->getData(), $this->getTag(), $lifetime);
    }
}
