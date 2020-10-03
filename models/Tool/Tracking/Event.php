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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Tracking;

use Pimcore\Model;

/**
 * @deprecated
 *
 * @method \Pimcore\Model\Tool\Tracking\Event\Dao getDao()
 */
class Event extends Model\AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $category;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $label;

    /**
     * @var int
     */
    public $timestamp;

    /**
     * @var string
     */
    public $data;

    /**
     * @param int $id
     *
     * @return Event|null
     */
    public static function getById($id)
    {
        try {
            $event = new self();
            $event->getDao()->getById(intval($id));

            return $event;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $category
     * @param string $action
     * @param string $label
     * @param int $day
     * @param int $month
     * @param int $year
     *
     * @return Event
     */
    public static function getByDate($category, $action, $label, $day, $month, $year)
    {
        $event = new self();
        try {
            $event->getDao()->getByDate($category, $action, $label, $day, $month, $year);
        } catch (\Exception $e) {
            $event->setTimestamp(mktime(1, 0, 0, $month, $day, $year));
            $event->setCategory($category);
            $event->setAction($action);
            $event->setLabel($label);
        }

        return $event;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param int $timestamp
     *
     * @return $this
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
