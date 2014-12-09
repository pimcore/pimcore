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

namespace Pimcore\Model\Tool\Tracking\Event;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("tracking_events");
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM tracking_events WHERE id = ?", $id);
        if (!$data["id"]) {
            throw new \Exception("there is no event for the requested id");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * @param $category
     * @param $action
     * @param $label
     * @param $day
     * @param $month
     * @param $year
     * @throws \Exception
     */
    public function getByDate($category, $action, $label, $day, $month, $year) {
        $data = $this->db->fetchRow("SELECT * FROM tracking_events WHERE category = ? AND action = ? AND label = ? AND day = ? AND month = ? AND year = ?", array((string) $category, (string) $action, (string) $label, $day, $month, $year));
        if (!$data["id"]) {
            throw new \Exception("there is no event for the requested id");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     *
     */
    public function save() {

        $data = array(
            "category" => (string) $this->model->getCategory(),
            "action" => (string) $this->model->getAction(),
            "label" => (string) $this->model->getLabel(),
            "data" => $this->model->getData(),
            "timestamp" => $this->model->getTimestamp(),
            "year" => (int) date("Y", $this->model->getTimestamp()),
            "month" => (int) date("m", $this->model->getTimestamp()),
            "day" => (int) date("d", $this->model->getTimestamp()),
            "dayOfWeek" => (int) date("N", $this->model->getTimestamp()),
            "dayOfYear" => (int) date("z", $this->model->getTimestamp())+1,
            "weekOfYear" => (int) date("W", $this->model->getTimestamp()),
            "hour" => (int) date("H", $this->model->getTimestamp()),
            "minute" => (int) date("i", $this->model->getTimestamp()),
            "second" => (int) date("s", $this->model->getTimestamp()),
        );

        $this->db->insertOrUpdate("tracking_events", $data);
    }
}
