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
 * @package    Dependency
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Dependency;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Element;

/**
 * @property \Pimcore\Model\Dependency $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Loads the relations for the given sourceId and type
     *
     * @param int $id
     * @param string $type
     */
    public function getBySourceId($id = null, $type = null, $start = null, $limit = null)
    {
        if ($id && $type) {
            $this->model->setSourceId($id);
            $this->model->setSourceType($type);
        }

        // requires
        $requiesQuery = 'SELECT * FROM dependencies WHERE sourceid = ? AND sourcetype = ?';

        if ($start !== null & $limit !== null) {
            $requiesQuery = sprintf($requiesQuery.' LIMIT %d,%d', $start, $limit);
        }

        $data = $this->db->fetchAll($requiesQuery,[$this->model->getSourceId(),$this->model->getSourceType()]);

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $this->model->addRequirement($d['targetid'], $d['targettype']);
            }
        }

        //get total count of requires records
        $requiresCount = (int) $this->db->fetchOne('SELECT COUNT(*) FROM dependencies WHERE sourceid = ? AND sourcetype = ?', [$this->model->getSourceId(),$this->model->getSourceType()]);
        $this->model->requiresTotalCount = $requiresCount;

        // required by
        $requiredByQuery = 'SELECT * FROM dependencies WHERE targetid = ? AND targettype = ?';

        if ($start !== null & $limit !== null) {
            $requiredByQuery = sprintf($requiredByQuery.' LIMIT %d,%d', $start, $limit);
        }
        
        $data = $this->db->fetchAll($requiredByQuery, [$this->model->getSourceId(), $this->model->getSourceType()]);

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $this->model->requiredBy[] = [
                    'id' => $d['sourceid'],
                    'type' => $d['sourcetype']
                ];
            }
        }

        //get total count of required by records
        $requiredByCount = (int) $this->db->fetchOne('SELECT COUNT(*) FROM dependencies WHERE targetid = ? AND targettype = ?', [$this->model->getSourceId(),$this->model->getSourceType()]);
        $this->model->requiredByTotalCount = $requiredByCount;
    }

    /**
     * Clear all relations in the database
     *
     * @param Element\ElementInterface $element
     */
    public function cleanAllForElement($element)
    {
        try {
            $id = $element->getId();
            $type = Element\Service::getElementType($element);

            //schedule for sanity check
            $data = $this->db->fetchAll('SELECT * FROM dependencies WHERE targetid = ? AND targettype = ?', [$id, $type]);
            if (is_array($data)) {
                foreach ($data as $row) {
                    $sanityCheck = new Element\Sanitycheck();
                    $sanityCheck->setId($row['sourceid']);
                    $sanityCheck->setType($row['sourcetype']);
                    $sanityCheck->save();
                }
            }

            $this->db->delete('dependencies', ['sourceid' => $id, 'sourcetype' => $type]);
            $this->db->delete('dependencies', ['targetid' => $id, 'targettype' => $type]);
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * Clear all relations in the database for current source id
     */
    public function clear()
    {
        try {
            $this->db->delete('dependencies', ['sourceid' => $this->model->getSourceId(), 'sourcetype' => $this->model->getSourceType()]);
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * Save to database
     */
    public function save()
    {
        foreach ($this->model->getRequires() as $r) {
            if ($r['id'] && $r['type']) {
                $this->db->insert('dependencies', [
                    'sourceid' => $this->model->getSourceId(),
                    'sourcetype' => $this->model->getSourceType(),
                    'targetid' => $r['id'],
                    'targettype' => $r['type']
                ]);
            }
        }
    }
}
