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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element\Note\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Element\Note elements
     *
     * @return array
     */
    public function load() {

        $notesData = $this->db->fetchCol("SELECT id FROM notes" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $notes = array();
        foreach ($notesData as $noteData) {
            if($note = Model\Element\Note::getById($noteData)) {
                $notes[] = $note;
            }
        }

        $this->model->setNotes($notes);
        return $notes;
    }


    public function loadIdList() {
        $notesIds = $this->db->fetchCol("SELECT id FROM notes" . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $notesIds;
    }

    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM notes " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }

}
