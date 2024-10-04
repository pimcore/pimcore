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

        $list = new Version\Listing();
        $ignoredIds = $list->setLoadAutoSave(true)->setCondition(' autoSave = 1 ')->loadIdList();

        // Not very pretty and should be solved using a repository....
        $dao = new Version();
        $dao = $dao->getDao();

        while (true) {
            $versions = $dao->maintenanceGetOutdatedVersions($elementTypes, $ignoredIds);

            if (count($versions) === 0) {
                break;
            }

            $counter = 0;

            $this->logger->debug('versions to check: ' . count($versions));

            $totalCount = count($versions);
            foreach ($versions as $index => $id) {
                if (!$version = Version::getById($id)) {
                    $ignoredIds[] = $id;
                    $this->logger->debug('Version with ' . $id . " not found\n");

                    continue;
                }

                $counter++;

                // do not delete public versions
                if ($version->getPublic()) {
                    $ignoredIds[] = $version->getId();

                    continue;
                }

                // do not delete versions referenced in the scheduler
                if ($dao->isVersionUsedInScheduler($version)) {
                    $ignoredIds[] = $version->getId();

                    continue;
                }

                $element = null;

                if ($version->getCtype() === 'document') {
                    $element = Document::getById($version->getCid());
                } elseif ($version->getCtype() === 'asset') {
                    $element = Asset::getById($version->getCid());
                } elseif ($version->getCtype() === 'object') {
                    $element = DataObject::getById($version->getCid());
                }

                if ($element instanceof Element\ElementInterface) {
                    $this->logger->debug('currently checking Element-ID: ' . $element->getId() . ' Element-Type: ' . Element\Service::getElementType($element) . ' in cycle: ' . $counter . '/' . $totalCount);

                    if ($element->getModificationDate() >= $version->getDate()) {
                        // delete version if it is outdated
                        $this->logger->debug('delete version: ' . $version->getId() . ' because it is outdated');
                        $version->delete();
                    } else {
                        $ignoredIds[] = $version->getId();
                        $this->logger->debug('do not delete version (' . $version->getId() . ") because version's date is newer than the actual modification date of the element. Element-ID: " . $element->getId() . ' Element-Type: ' . Element\Service::getElementType($element));
                    }
                } else {
                    // delete version if the corresponding element doesn't exist anymore
                    $this->logger->debug('delete version (' . $version->getId() . ") because the corresponding element doesn't exist anymore");
                    $version->delete();
                }

                // call the garbage collector if memory consumption is > 100MB
                if (memory_get_usage() > 100000000) {
                    Pimcore::collectGarbage();
                }
            }
        }
    }
}
