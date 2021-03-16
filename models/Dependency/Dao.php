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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Element;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

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
     *
     * @return void
     */
    public function getBySourceId($id = null, $type = null)
    {
        if ($id && $type) {
            $this->model->setSourceId($id);
            $this->model->setSourceType($type);
        }

        // requires
        $data = $this->db->fetchAll('SELECT `targetid`,`targettype`  FROM dependencies WHERE sourceid = ? AND sourcetype = ?', [$this->model->getSourceId(), $this->model->getSourceType()]);

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
     *
     * @return void
     */
    public function cleanAllForElement($element)
    {
        try {
            $id = $element->getId();
            $type = Element\Service::getElementType($element);

            //schedule for sanity check
            $data = $this->db->fetchAll('SELECT `sourceid`, `sourcetype` FROM dependencies WHERE targetid = ? AND targettype = ?', [$id, $type]);
            if (is_array($data)) {
                foreach ($data as $row) {
                    $sanityCheck = new Element\Sanitycheck();
                    $sanityCheck->setId($row['sourceid']);
                    $sanityCheck->setType($row['sourcetype']);
                    $sanityCheck->save();
                }
            }

            $this->db->selectAndDeleteWhere('dependencies', 'id', $this->db->quoteInto('sourceid = ?', $id) . ' AND  ' . $this->db->quoteInto('sourcetype = ?', $type));
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * Clear all relations in the database for current source id
     *
     * @return void
     */
    public function clear()
    {
        try {
            $this->db->selectAndDeleteWhere('dependencies', 'id', $this->db->quoteInto('sourceid = ?', $this->model->getSourceId()) . ' AND  ' . $this->db->quoteInto('sourcetype = ?', $this->model->getSourceType()));
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * Save to database
     *
     * @return void
     */
    public function save()
    {
        // get existing dependencies
        $existingDependenciesRaw = $this->db->fetchAll('SELECT id, targetType, targetId FROM dependencies WHERE sourceType= ? AND sourceId = ?',
            [$this->model->getSourceType(), $this->model->getSourceId()]);

        $existingDepencies = [];
        foreach ($existingDependenciesRaw as $dep) {
            $targetType = $dep['targetType'];
            $targetId = $dep['targetId'];
            $rowId = $dep['id'];
            if (!isset($existingDepencies[$targetType])) {
                $existingDepencies[$targetType] = [];
            }
            $existingDepencies[$targetType][$targetId] = $rowId;
        }

        $requires = $this->model->getRequires();

        // now calculate the delta, everything that stays in existingDependencies has to be deleted
        $newData = [];
        foreach ($requires as $r) {
            $targetType = $r['type'];
            $targetId = $r['id'];
            if ($targetType && $targetId) {
                if (!isset($existingDepencies[$targetType][$targetId])) {
                    // mark for insertion
                    $newData[] = $r;
                } else {
                    // unmark for deletion
                    unset($existingDepencies[$targetType][$targetId]);
                }
            }
        }

        // collect all IDs for deletion
        $idsForDeletion = [];
        foreach ($existingDepencies as $targetType => $targetIds) {
            foreach ($targetIds as $targetId => $rowId) {
                $idsForDeletion[] = $rowId;
            }
        }

        if ($idsForDeletion) {
            $idString = implode(',', $idsForDeletion);
            $this->db->deleteWhere('dependencies', 'id IN (' . $idString . ')');
        }

        if ($newData) {
            foreach ($newData as $target) {
                try {
                    $this->db->insert('dependencies', [
                        'sourceid' => $this->model->getSourceId(),
                        'sourcetype' => $this->model->getSourceType(),
                        'targetid' => $target['id'],
                        'targettype' => $target['type'],
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                }
            }
        }
    }

    /**
     * Loads the relations that need the given source element
     *
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function getRequiredBy($offset = null, $limit = null)
    {
        $query = 'SELECT sourceid, sourcetype FROM dependencies WHERE targetid = ? AND targettype = ?';

        if ($offset !== null && $limit !== null) {
            $query = sprintf($query . ' LIMIT %d,%d', $offset, $limit);
        }

        $data = $this->db->fetchAll($query, [$this->model->getSourceId(), $this->model->getSourceType()]);

        $requiredBy = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $requiredBy[] = [
                    'id' => $d['sourceid'],
                    'type' => $d['sourcetype'],
                ];
            }
        }

        return $requiredBy;
    }

    /**
     * @param string|null $orderBy
     * @param string|null $orderDirection
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return array
     */
    public function getRequiredByWithPath($offset = null, $limit = null, $orderBy = null, $orderDirection = null)
    {
        $targetId = intval($this->model->getSourceId());

        if (in_array($this->model->getSourceType(), ['object', 'document', 'asset'])) {
            $targetType = $this->model->getSourceType();
        } else {
            throw new SuspiciousOperationException('Illegal source type ' . $this->model->getSourceType());
        }

        if (!in_array($orderBy, ['id', 'type', 'path'])) {
            $orderBy = 'id';
        }

        if (!in_array($orderDirection, ['ASC', 'DESC'])) {
            $orderDirection = 'ASC';
        }

        $query = '
            SELECT id, type, path
            FROM (
                SELECT d.sourceid as id, d.sourcetype as type, CONCAT(o.o_path, o.o_key) as path
                FROM dependencies d
                JOIN objects o ON o.o_id = d.sourceid
                WHERE d.targetid = ' . $targetId . " AND  d.targettype = '" . $targetType. "' AND d.sourceType = 'object'
                UNION
                SELECT d.sourceid as id, d.sourcetype as type, CONCAT(doc.path, doc.key) as path
                FROM dependencies d
                JOIN documents doc ON doc.id = d.sourceid
                WHERE d.targetid = " . $targetId . " AND  d.targettype = '" . $targetType. "' AND d.sourceType = 'document'
                UNION
                SELECT d.sourceid as id, d.sourcetype as type, CONCAT(a.path, a.filename) as path
                FROM dependencies d
                JOIN assets a ON a.id = d.sourceid
                WHERE d.targetid = " . $targetId . " AND  d.targettype = '" . $targetType. "' AND d.sourceType = 'asset'
            ) dep
            ORDER BY " . $orderBy . ' ' . $orderDirection;

        if (is_int($offset) && is_int($limit)) {
            $query .= ' LIMIT ' . $offset . ', ' . $limit;
        }

        $requiredBy = $this->db->fetchAll($query);

        if (is_array($requiredBy) && count($requiredBy) > 0) {
            return $requiredBy;
        } else {
            return [];
        }
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
