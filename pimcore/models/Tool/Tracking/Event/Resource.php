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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Tracking_Event_Resource extends Pimcore_Model_Resource_Abstract {

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

    public function save() {

        $data = array(
            "category" => $this->model->getCategory(),
            "action" => $this->model->getAction(),
            "label" => $this->model->getLabel(),
            "value" => $this->model->getValue(),
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

        if(!$this->model->getId()) {
            $this->db->insert("tracking_events", $data);
        } else {
            $data["id"] = $this->model->getId();
            $this->db->update("tracking_events", $data, "id = '" . $this->model->getId() . "'");
        }
    }

}
