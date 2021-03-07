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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Config;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;

final class VersionsCleanupTask implements TaskInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(LoggerInterface $logger, Config $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $conf['document'] = $this->config['documents']['versions'] ?? null;
        $conf['asset'] = $this->config['assets']['versions'] ?? null;
        $conf['object'] = $this->config['objects']['versions'] ?? null;

        $elementTypes = [];

        foreach ($conf as $elementType => $tConf) {
            $versioningType = 'steps';
            $value = $tConf['steps'] ?? 10;

            if (isset($tConf['days']) && (int)$tConf['days'] > 0) {
                $versioningType = 'days';
                $value = (int)$tConf['days'];
            }

            if ($versioningType) {
                $elementTypes[] = [
                    'elementType' => $elementType,
                    $versioningType => $value,
                ];
            }
        }

        $ignoredIds = [];

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

            if (is_array($versions) && !empty($versions)) {
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
                        \Pimcore::collectGarbage();
                    }
                }
            }
        }
    }
}
