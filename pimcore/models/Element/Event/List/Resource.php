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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Event_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Element_Event elements
     *
     * @return array
     */
    public function load() {

        $eventsData = $this->db->fetchCol("SELECT id FROM events" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $events = array();
        foreach ($eventsData as $eventData) {
            if($event = Element_Event::getById($eventData)) {
                $events[] = $event;
            }
        }

        $this->model->setEvents($events);
        return $events;
    }

    public function getTotalCount() {

        try {
            $amount = $this->db->fetchOne("SELECT COUNT(*) as amount FROM events " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {

        }

        return $amount;
    }

}
