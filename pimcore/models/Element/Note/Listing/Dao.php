<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element\Note\Listing;

use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao {

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
