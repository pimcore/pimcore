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

use Pimcore\Maintenance\TaskInterface;
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
                'disable_events' => $tConf['disable_events'] ?? false,
            ];
        }

        // Not very pretty and should be solved using a repository....
        $dao = new Version();
        $dao = $dao->getDao();

        // Delete orphan versions
        $orphanVersions = $dao->getOrphanedVersionsAndOutdatedAutoSave($elementTypes);
        $dao->deleteVersions($orphanVersions, $elementTypes);

        // Delete outdated versions
        $versions = $dao->maintenanceGetOutdatedVersions($elementTypes);
        $totalVersions =  count($versions);
        if ($totalVersions === 0) {
            return;
        }

        $this->logger->debug('versions to check: ' . $totalVersions);
        $dao->deleteVersions($versions, $elementTypes);
    }
}
