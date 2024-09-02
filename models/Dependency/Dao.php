<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Dependency;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Pimcore;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Messenger\SanityCheckMessage;
use Pimcore\Model;
use Pimcore\Model\Element;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

/**
 * @internal
 *
 * @property \Pimcore\Model\Dependency $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Loads the relations for the given sourceId and type
     *
     *
     */
    public function getBySourceId(int $id = null, string $type = null): void
    {
        if ($id && $type) {
            $this->model->setSourceId($id);
            $this->model->setSourceType($type);
        }

        // requires
        $data = $this->db->fetchAllAssociative('SELECT dependencies.targetid,dependencies.targettype
            FROM dependencies
            LEFT JOIN objects ON dependencies.targettype="object" AND dependencies.targetid=objects.id
            LEFT JOIN assets ON dependencies.targettype="asset" AND dependencies.targetid=assets.id
            LEFT JOIN documents ON dependencies.targettype="document" AND dependencies.targetid=documents.id
            WHERE dependencies.sourceid = ? AND dependencies.sourcetype = ?
            ORDER BY objects.path, objects.key, documents.path, documents.key, assets.path, assets.filename',
            [$this->model->getSourceId(), $this->model->getSourceType()]);

        foreach ($data as $d) {
            $this->model->addRequirement($d['targetid'], $d['targettype']);
        }
    }

    public function getFilterRequiresByPath(
        int $offset = null,
        int $limit = null,
        string $value = null,
        string $orderBy = null,
        string $orderDirection = null): array
    {

        $sourceId = (int)$this->model->getSourceId();

        if (in_array($this->model->getSourceType(), ['object', 'document', 'asset'])) {
            $sourceType = $this->model->getSourceType();
        } else {
            throw new SuspiciousOperationException('Illegal source type ' . $this->model->getSourceType());
        }

        if (!in_array($orderBy, ['id', 'type', 'path'])) {
            $orderBy = 'id';
        }

        if (!in_array($orderDirection, ['ASC', 'DESC'])) {
            $orderDirection = 'ASC';
        }

        //filterRequiresByPath
        $query = "
        SELECT id, type
        FROM (
            SELECT d.targetid as id, d.targettype as type
            FROM dependencies d
            INNER JOIN objects o ON o.id = d.targetid AND d.targettype= 'object'
            WHERE d.sourcetype = '" . $sourceType. "' AND d.sourceid = " . $sourceId . " AND LOWER(CONCAT(o.path, o.key)) RLIKE '".$value."'
            UNION
            SELECT d.targetid as id, d.targettype as type
            FROM dependencies d
            INNER JOIN documents doc ON doc.id = d.targetid AND d.targettype= 'document'
            WHERE d.sourcetype = '" . $sourceType. "' AND d.sourceid = " . $sourceId . " AND LOWER(CONCAT(doc.path, doc.key)) RLIKE '".$value."'
            UNION
            SELECT d.targetid as id, d.targettype as type
            FROM dependencies d
            INNER JOIN assets a ON a.id = d.targetid AND d.targettype= 'asset'
            WHERE d.sourcetype = '" . $sourceType. "' AND d.sourceid = " . $sourceId . " AND LOWER(CONCAT(a.path, a.filename)) RLIKE '".$value."'
        ) dep
        ORDER BY " . $orderBy . ' ' . $orderDirection;

        if ($offset !== null && $limit !== null) {
            $query = sprintf($query . ' LIMIT %d,%d', $offset, $limit);
        }

        $requiresByPath = $this->db->fetchAllAssociative($query);

        if (count($requiresByPath) > 0) {
            return $requiresByPath;
        } else {
            return [];
        }
    }

    public function getFilterRequiredByPath(
        int $offset = null,
        int $limit = null,
        string $value = null,
        string $orderBy = null,
        string $orderDirection = null
    ): array {

        $targetId = (int)$this->model->getSourceId();

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

        //filterRequiredByPath
        $query = "
        SELECT id, type
        FROM (
            SELECT d.sourceid as id, d.sourcetype as type
            FROM dependencies d
            INNER JOIN objects o ON o.id = d.sourceid AND d.targettype= 'object'
            WHERE d.targettype = '" . $targetType. "' AND d.targetid = " . $targetId . " AND LOWER(CONCAT(o.path, o.key)) RLIKE '".$value."'
            UNION
            SELECT d.sourceid as id, d.sourcetype as type
            FROM dependencies d
            INNER JOIN documents doc ON doc.id = d.sourceid AND d.targettype= 'document'
            WHERE d.targettype = '" . $targetType. "' AND d.targetid = " . $targetId . " AND LOWER(CONCAT(doc.path, doc.key)) RLIKE '".$value."'
            UNION
            SELECT d.sourceid as id, d.sourcetype as type
            FROM dependencies d
            INNER JOIN assets a ON a.id = d.sourceid AND d.targettype= 'asset'
            WHERE d.targettype = '" . $targetType. "' AND d.targetid = " . $targetId . " AND LOWER(CONCAT(a.path, a.filename)) RLIKE '".$value."'
        ) dep
        ORDER BY " . $orderBy . ' ' . $orderDirection;

        if ($offset !== null && $limit !== null) {
            $query = sprintf($query . ' LIMIT %d,%d', $offset, $limit);
        }

        $requiredByPath = $this->db->fetchAllAssociative($query);

        if (count($requiredByPath) > 0) {
            return $requiredByPath;
        } else {
            return [];
        }
    }

    /**
     * Clear all relations in the database
     *
     *
     */
    public function cleanAllForElement(Element\ElementInterface $element): void
    {
        try {
            $id = $element->getId();
            $type = Element\Service::getElementType($element);

            //schedule for sanity check
            $data = $this->db->fetchAllAssociative('SELECT `sourceid`, `sourcetype` FROM dependencies WHERE targettype = ? AND targetid = ?', [$type, $id]);
            foreach ($data as $row) {
                Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                    new SanityCheckMessage($row['sourcetype'], $row['sourceid'])
                );
            }

            Helper::selectAndDeleteWhere($this->db, 'dependencies', 'id', Helper::quoteInto($this->db, 'sourceid = ?', $id) . ' AND  ' . Helper::quoteInto($this->db, 'sourcetype = ?', $type));
        } catch (Exception $e) {
            Logger::error((string) $e);
        }
    }

    /**
     * Clear all relations in the database for current source id
     *
     */
    public function clear(): void
    {
        try {
            Helper::selectAndDeleteWhere($this->db, 'dependencies', 'id', Helper::quoteInto($this->db, 'sourceid = ?', $this->model->getSourceId()) . ' AND  ' . Helper::quoteInto($this->db, 'sourcetype = ?', $this->model->getSourceType()));
        } catch (Exception $e) {
            Logger::error((string) $e);
        }
    }

    /**
     * Save to database
     *
     */
    public function save(): void
    {
        // get existing dependencies
        $existingDependenciesRaw = $this->db->fetchAllAssociative('SELECT id, targetType, targetId FROM dependencies WHERE sourceType= ? AND sourceId = ?',
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
            $this->db->executeStatement('DELETE FROM dependencies WHERE id IN (' . $idString . ')');
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
     *
     */
    public function getRequiredBy(int $offset = null, int $limit = null): array
    {
        $query = '
            SELECT dependencies.sourceid, dependencies.sourcetype FROM dependencies
            LEFT JOIN objects ON dependencies.sourceid=objects.id AND dependencies.sourcetype="object"
            LEFT JOIN assets ON dependencies.sourceid=assets.id AND dependencies.sourcetype="asset"
            LEFT JOIN documents ON dependencies.sourceid=documents.id AND dependencies.sourcetype="document"
            WHERE dependencies.targettype = ? AND dependencies.targetid = ?
            ORDER BY objects.path, objects.key, documents.path, documents.key, assets.path, assets.filename
        ';

        if ($offset !== null && $limit !== null) {
            $query = sprintf($query . ' LIMIT %d,%d', $offset, $limit);
        }

        $data = $this->db->fetchAllAssociative($query, [$this->model->getSourceType(), $this->model->getSourceId()]);

        $requiredBy = [];

        foreach ($data as $d) {
            $requiredBy[] = [
                'id' => $d['sourceid'],
                'type' => $d['sourcetype'],
            ];
        }

        return $requiredBy;
    }

    public function getRequiredByWithPath(int $offset = null, int $limit = null, string $orderBy = null, string $orderDirection = null): array
    {
        $targetId = $this->model->getSourceId();

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

        $query = "
            SELECT id, type, path
            FROM (
                SELECT d.sourceid as id, d.sourcetype as `type`, CONCAT(o.path, o.key) as `path`
                FROM dependencies d
                JOIN objects o ON o.id = d.sourceid
                WHERE d.targettype = '" . $targetType. "' AND d.targetid = " . $targetId . " AND d.sourceType = 'object'
                UNION
                SELECT d.sourceid as id, d.sourcetype as `type`, CONCAT(doc.path, doc.key) as `path`
                FROM dependencies d
                JOIN documents doc ON doc.id = d.sourceid
                WHERE d.targettype = '" . $targetType. "' AND d.targetid = " . $targetId . " AND d.sourceType = 'document'
                UNION
                SELECT d.sourceid as id, d.sourcetype as `type`, CONCAT(a.path, a.filename) as `path`
                FROM dependencies d
                JOIN assets a ON a.id = d.sourceid
                WHERE d.targettype = '" . $targetType. "' AND d.targetid = " . $targetId . " AND d.sourceType = 'asset'
            ) dep
            ORDER BY " . $orderBy . ' ' . $orderDirection;

        if (is_int($offset) && is_int($limit)) {
            $query .= ' LIMIT ' . $offset . ', ' . $limit;
        }

        return $this->db->fetchAllAssociative($query);
    }

    /**
     * get total count of required by records
     *
     */
    public function getRequiredByTotalCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM dependencies WHERE targettype = ? AND targetid = ?', [$this->model->getSourceType(), $this->model->getSourceId()]);
    }
}
