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

namespace Pimcore\Model\Version\Adapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class DatabaseVersionStorageAdapter implements VersionStorageAdapterInterface
{
    CONST versionsTableName = "versionsData";
    protected Connection $db;

    public function __construct()
    {
        $dbConnectionString = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['versions']['database_connection'] ?? "";
        if(empty($dbConnectionString) === true) {
            throw new \Exception("configuration value 'database_connection' is not set");
        }
        $this->db = \Pimcore::getContainer()->get($dbConnectionString);
    }

    protected function binaryFileHashExists(string $binaryFileHash) {

    }

    public function save(int $id,
                         int $cId,
                         string $cType,
                         string $metaData,
                         mixed $binaryDataStream = null,
                         string $binaryFileHash = null,
                         int $binaryFileId = null): ?string
    {

        if(isset($binaryDataStream) === true &&
            isset($binaryFileId) === false) {
            $contents = stream_get_contents($binaryDataStream);
        }

        $sql = "insert into " . self::versionsTableName . "(id, cid, ctype, metaData, binaryData) values (:id, :cid, :ctype, :metaData, :binaryData)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue("id", $id);
        $stmt->bindValue("cid", $cId);
        $stmt->bindValue("ctype", $cType);
        $stmt->bindValue("metaData", $metaData);
        $stmt->bindValue("binaryData", $contents ?? null, ParameterType::LARGE_OBJECT);
        $stmt->executeQuery();

        return null;
    }

    /**
     * @param int $id
     * @param int $cId
     * @param string $cType
     * @param bool $binaryData
     * @return mixed
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    protected function loadData(int    $id,
                                int    $cId,
                                string $cType,
                                bool   $binaryData = false): mixed {

        $dataColumn = $binaryData ? 'binaryData' : 'metaData';
        $sql = "SELECT " . $dataColumn . " FROM " . self::versionsTableName . " WHERE id = :id AND cid = :cid and ctype = :ctype";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue("id", $id);
        $stmt->bindValue("cid", $cId);
        $stmt->bindValue("ctype", $cType);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAssociative();
        if(empty($result) === false) {
            return $result[$dataColumn] ?? null;
        }
        return null;
    }

    public function loadMetaData(int $id,
                                 int $cId,
                                 string $cType,
                                 string $storageType): ?string
    {
        return $this->loadData($id, $cId, $cType);
    }

    public function loadBinaryData(int $id,
                                   int $cId,
                                   string $cType,
                                   string $storageType,
                                   int $binaryFileId = null): mixed
    {
        $binaryData = $this->loadData($binaryFileId ?? $id, $cId, $cType, true);
        if(isset($binaryData) === true) {
            $fileName = tmpfile();
            fwrite($fileName, $binaryData);
            return $fileName;
        }
        return null;
    }

    public function delete(int $id,
                           int $cId,
                           string $cType,
                           bool $isBinaryHashInUse,
                           int $binaryFileId = null): void
    {
        $sql = "delete from " . self::versionsTableName . " where id=:id and cid=:cid and ctype=:ctype";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue("id", $id);
        $stmt->bindValue("cid", $cId);
        $stmt->bindValue("ctype", $cType);
        $stmt->executeQuery();
    }
}
