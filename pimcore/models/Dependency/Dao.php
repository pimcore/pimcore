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
    public function getBySourceId($id = null, $type = null)
    {
        if ($id && $type) {
            $this->model->setSourceId($id);
            $this->model->setSourceType($type);
        }

        // requires
        $data = $this->db->fetchAll('SELECT * FROM dependencies WHERE sourceid = ? AND sourcetype = ?', [$this->model->getSourceId(), $this->model->getSourceType()]);

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $this->model->addRequirement($d['targetid'], $d['targettype']);
            }
        }
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

    /**
     * Loads the relations that need the given source object
     *
     * @param int $offset
     * @param int $limit
     */
    public function getRequiredBy($offset = null, $limit = null)
    {
        $query = 'SELECT * FROM dependencies WHERE targetid = ? AND targettype = ?';

        if ($offset !== null & $limit !== null) {
            $query = sprintf($query.' LIMIT %d,%d', $offset, $limit);
        }

        $data = $this->db->fetchAll($query, [$this->model->getSourceId(), $this->model->getSourceType()]);

        $requiredBy = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $requiredBy[] = [
                    'id' => $d['sourceid'],
                    'type' => $d['sourcetype']
                ];
            }
        }

        return $requiredBy;
    }

    /**
     * get total count of required by records
     *
     * @return int
     */
    public function getRequiredByTotalCount()
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM dependencies WHERE targetid = ? AND targettype = ?', [$this->model->getSourceId(), $this->model->getSourceType()]);
    }
}
