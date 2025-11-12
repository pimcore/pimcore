<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Maintenance\Tasks;

use Pimcore;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Version;
use Pimcore\SystemSettingsConfig;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class VersionsCleanupTask implements TaskInterface
{
    public function __construct(private LoggerInterface $logger, private SystemSettingsConfig $config)
    {
    }

    public function execute(): void
    {
        $this->doVersionCleanup();
        $this->doAutoSaveVersionCleanup();
    }

    private function doAutoSaveVersionCleanup(): void
    {
        $date = \Carbon\Carbon::now();
        $date->subHours(72);

        $list = new Version\Listing();
        $ids = $list->setLoadAutoSave(true)
            ->setCondition(' `autoSave` = 1 AND `date` < ' . $date->getTimestamp())
            ->loadIdList();

        $this->logger->debug('Auto-save versions to delete: ' . count($ids));
        foreach ($ids as $i => $id) {
            $this->logger->debug('Deleting auto-save version: ' . $id);
            $version = Version::getById($id);
            $version->delete();
        }
    }

    private function doVersionCleanup(): void
    {
        $systemSettingsConfig = $this->config->getSystemSettingsConfig();
        $conf = [
            'document' => $systemSettingsConfig['documents']['versions'] ?? null,
            'asset' => $systemSettingsConfig['assets']['versions'] ?? null,
            'object' => $systemSettingsConfig['objects']['versions'] ?? null,
        ];

        $elementTypes = [];

        foreach ($conf as $elementType => $tConf) {
            $versioningType = 'steps';
            //skip cleanup if element is null
            if (is_null($tConf)) {
                continue;
            }
            //skip cleanup if both, 'steps' & 'days', is null
            if (is_null($tConf['steps']) && is_null($tConf['days'])) {
                continue;
            }
            $value = $tConf['steps'] ?? 10;

            if (isset($tConf['days'])) {
                $versioningType = 'days';
                $value = (int)$tConf['days'];
            }

            $elementTypes[] = [
                'elementType' => $elementType,
                $versioningType => $value,
            ];
        }

        // Not very pretty and should be solved using a repository....
        $dao = new Version();
        $dao = $dao->getDao();

        // Delete orphan versions
        $orphanVersions = $dao->getOrphanedVersions($elementTypes);

        foreach ($orphanVersions as $versionId) {
            $version = Version::getById($versionId);
            $this->logger->debug('delete version (' . $versionId . ") because the corresponding element doesn't exist anymore");
            $version->delete();
        }

        $versions = $dao->maintenanceGetOutdatedVersions($elementTypes);
        $totalVersions =  count($versions);
        if ($totalVersions === 0) {
            return;
        }

        $this->logger->debug('versions to check: ' . $totalVersions);

        foreach ($versions as $index => $id) {
            $version = Version::getById($id);
            $version->delete();
        }
    }
}
