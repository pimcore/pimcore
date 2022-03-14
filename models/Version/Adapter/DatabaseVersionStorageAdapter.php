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
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\ParameterType;

class DatabaseVersionStorageAdapter implements VersionStorageAdapterInterface
{
    CONST versionsTableName = "versionsData";

    protected function getDb() : Connection
    {
        return \Pimcore::getContainer()->get($this->database_connection);
    }

    public function __construct(protected string $database_connection)
    {
    }

    public function save(int $id,
                         int $cId,
                         string $cType,
                         string $storageType,
                         string $metaData,
                         mixed $binaryDataStream = null,
                         string $binaryFileHash = null,
                         int $binaryFileId = null): void
    {

        if(isset($binaryDataStream) === true &&
            isset($binaryFileId) === false) {
            $contents = stream_get_contents($binaryDataStream);
        }

        $this->getDb()->executeStatement("insert into " . self::versionsTableName . "(id, cid, ctype, metaData, binaryData) values (:id, :cid, :ctype, :metaData, :binaryData)",
            [
                'id' => $id,
                'cid' => $cId,
                'ctype' => $cType,
                'metaData' => $metaData,
                'binaryData' => $contents ?? null
            ]);
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

        $data = null;
        $dataColumn = $binaryData ? 'binaryData' : 'metaData';
        $resultSet = $this->getDb()->executeQuery("SELECT " . $dataColumn . " FROM " . self::versionsTableName . " WHERE id = :id AND cid = :cid and ctype = :ctype",
            [
                'id' => $id,
                'cid' => $cId,
                'ctype' => $cType
            ]);

        if ($resultSet instanceof Result) {
            $data = $resultSet->fetchOne();
            $resultSet->free();
        }
        return $data;
    }

    public function loadMetaData(int $id,
                                 int $cId,
                                 string $cType,
                                 string $storageType = null): ?string
    {
        return $this->loadData($id, $cId, $cType);
    }

    /**
     * @param string $data
     * @return mixed
     */
    protected function getStream(string $data): mixed
    {
        if($data) {
            $fileName = tmpfile();
            fwrite($fileName, $data);
            return $fileName;
        }
        return null;
    }

    public function loadBinaryData(int $id,
                                   int $cId,
                                   string $cType,
                                   string $storageType = null,
                                   int $binaryFileId = null): mixed
    {
        $binaryData = $this->loadData($binaryFileId ?? $id, $cId, $cType, true);
        return $this->getStream($binaryData);

    }

    public function delete(int $id,
                           int $cId,
                           string $cType,
                           string $storageType,
                           bool $isBinaryHashInUse,
                           int $binaryFileId = null): void
    {
        $this->getDb()->executeStatement("delete from " . self::versionsTableName . " where id=:id and cid=:cid and ctype=:ctype",
            [
                'id' => $id,
                'cid' => $cId,
                'ctype' => $cType
            ]);
    }

    public function getBinaryFileStream(int $id, int $cId, string $cType, int $binaryFileId = null): mixed
    {
        return $this->loadBinaryData($id,
                                    $cId,
                                    $cType,
                                    null,
                                    $binaryFileId);

    }

    public function getFileStream(int $id, int $cId, string $cType): mixed
    {
        $metaData = $this->loadMetaData($id,
                                        $cId,
                                        $cType);
        return $this->getStream($metaData);
    }

    public function getStorageType(int $metaDataSize = null,
                                   int $binaryDataSize = null): string
    {
        return "db";
    }
}
