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
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Note\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Element\Note\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Element\Note elements
     *
     * @return array
     */
    public function load()
    {
        $notesData = $this->db->fetchCol("SELECT id FROM notes" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $notes = [];
        foreach ($notesData as $noteData) {
            if ($note = Model\Element\Note::getById($noteData)) {
                $notes[] = $note;
            }
        }

        $this->model->setNotes($notes);

        return $notes;
    }

    /**
     * @return array
     */
    public function loadIdList()
    {
        $notesIds = $this->db->fetchCol("SELECT id FROM notes" . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $notesIds;
    }

    /**
     * @return int
     *
     * @todo: $amount could not be defined, so this could cause an issue
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM notes " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
