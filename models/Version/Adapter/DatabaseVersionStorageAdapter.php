<?php
declare(strict_types=1);

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

use Doctrine\DBAL;
use Pimcore\Model\Version;

/**
 * @internal
 */
class DatabaseVersionStorageAdapter implements VersionStorageAdapterInterface
{
    const versionsTableName = 'versionsData';

    public function __construct(protected DBAL\Connection $databaseConnection)
    {
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        if (isset($binaryDataStream) === true &&
            empty($version->getBinaryFileId()) === true) {
            $contents = stream_get_contents($binaryDataStream);
        }

        $query = 'INSERT INTO ' . self::versionsTableName . '(`id`, `cid`, `ctype`, `metaData`, `binaryData`) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE `metaData` = ?, `binaryData` = ?';

        $this->databaseConnection->executeQuery(
            $query,
            [
                $version->getId(),
                $version->getCid(),
                $version->getCtype(),
                $metaData,
                $contents ?? null,
                $metaData,
                $contents ?? null,
            ]
        );
    }

    /**
     *
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function loadData(int $id,
        int $cId,
        string $cType,
        bool $binaryData = false): mixed
    {
        $dataColumn = $binaryData ? 'binaryData' : 'metaData';

        return $this->databaseConnection->fetchOne('SELECT ' . $dataColumn . ' FROM ' . self::versionsTableName . ' WHERE id = :id AND cid = :cid and ctype = :ctype',
            [
                'id' => $id,
                'cid' => $cId,
                'ctype' => $cType,
            ]);
    }

    public function loadMetaData(Version $version): ?string
    {
        return $this->loadData($version->getId(), $version->getCid(), $version->getCtype());
    }

    protected function getStream(string $data): mixed
    {
        if ($data) {
            $fileName = tmpfile();
            fwrite($fileName, $data);

            return $fileName;
        }

        return null;
    }

    public function loadBinaryData(Version $version): mixed
    {
        $binaryData = $this->loadData($version->getBinaryFileId() ?? $version->getId(), $version->getCid(), $version->getCtype(), true);

        return $this->getStream($binaryData);
    }

    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $this->databaseConnection->delete(self::versionsTableName, [
                                                'id' => $version->getId(),
                                                'cid' => $version->getCid(),
                                                'ctype' => $version->getCtype(),
                                            ]);
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->loadBinaryData($version);
    }

    public function getFileStream(Version $version): mixed
    {
        $metaData = $this->loadMetaData($version);

        return $this->getStream($metaData);
    }

    public function getStorageType(int $metaDataSize = null,
        int $binaryDataSize = null): string
    {
        return 'db';
    }
}
