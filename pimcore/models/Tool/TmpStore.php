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

class TmpStore extends Model\AbstractModel {

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
    protected static function getInstance () {
        if(!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param $id
     * @param $data
     * @param null $tag
     * @return mixed
     */
    public static function add ($id, $data, $tag = null) {
        $instance = self::getInstance();
        return $instance->getResource()->add($id, $data, $tag);
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function delete($id) {
        $instance = self::getInstance();
        return $instance->getResource()->delete($id);
    }

    /**
     * @param $id
     * @return null|TmpStore
     */
    public static function get($id) {
        $item = new self;
        if($item->getById($id)) {
            return $item;
        }
        return null;
    }

    /**
     * @param int $expiryTime
     */
    public static function cleanup($expiryTime = null) {
        if(!$expiryTime) {
            $expiryTime = 86400;
        }
        $instance = self::getInstance();
        $instance->getResource()->cleanup($expiryTime);
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
}
