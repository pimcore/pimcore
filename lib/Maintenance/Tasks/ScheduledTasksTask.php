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

use Exception;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Schedule\Task\Listing;
use Pimcore\Model\User;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class ScheduledTasksTask implements TaskInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $list = new Listing();
        $list->setCondition('active = 1 AND date < ?', time());
        $tasks = $list->load();

        foreach ($tasks as $task) {
            $taskUser = User::getById($task->getUserId());

            try {
                if ($task->getCtype() === 'document') {
                    $document = Document::getById($task->getCid());
                    if ($document instanceof Document) {
                        if ($task->getAction() === 'publish-version' && $task->getVersion() && $document->isAllowed('publish', $taskUser) && $document->isAllowed('versions', $taskUser)) {
                            if ($version = Version::getById($task->getVersion())) {
                                $document = $version->getData();
                                if ($document instanceof Document) {
                                    $document->setPublished(true);
                                    $document->save();
                                } else {
                                    $this->logger->error('Schedule\\Task\\Executor: Could not restore document from version data.');
                                }
                            } else {
                                $this->logger->error('Schedule\\Task\\Executor: Version [ '.$task->getVersion().' ] does not exist.');
                            }
                        } elseif ($task->getAction() === 'publish' && $document->isAllowed('publish', $taskUser)) {
                            $document->setPublished(true);
                            $document->save();
                        } elseif ($task->getAction() === 'unpublish' && $document->isAllowed('unpublish', $taskUser)) {
                            $document->setPublished(false);
                            $document->save();
                        } elseif ($task->getAction() === 'delete' && $document->isAllowed('delete', $taskUser)) {
                            $document->delete();
                        }
                    }
                } elseif ($task->getCtype() === 'asset') {
                    $asset = Asset::getById($task->getCid());

                    if ($asset instanceof Asset) {
                        if ($task->getAction() === 'publish-version' && $task->getVersion() && $asset->isAllowed('publish', $taskUser) && $asset->isAllowed('versions', $taskUser)) {
                            if ($version = Version::getById($task->getVersion())) {
                                $asset = $version->getData();
                                if ($asset instanceof Asset) {
                                    $asset->save();
                                } else {
                                    $this->logger->error('Schedule\\Task\\Executor: Could not restore asset from version data.');
                                }
                            } else {
                                $this->logger->error('Schedule\\Task\\Executor: Version [ '.$task->getVersion().' ] does not exist.');
                            }
                        } elseif ($task->getAction() === 'delete' && $asset->isAllowed('delete', $taskUser)) {
                            $asset->delete();
                        }
                    }
                } elseif ($task->getCtype() === 'object') {
                    $object = DataObject::getById($task->getCid());

                    if ($object instanceof DataObject\Concrete) {
                        if ($task->getAction() === 'publish-version' && $task->getVersion() && $object->isAllowed('publish', $taskUser) && $object->isAllowed('versions', $taskUser)) {
                            if ($version = Version::getById($task->getVersion())) {
                                $object = $version->getData();
                                if ($object instanceof DataObject\Concrete) {
                                    $object->setPublished(true);
                                    $object->save();
                                } else {
                                    $this->logger->error('Schedule\\Task\\Executor: Could not restore object from version data.');
                                }
                            } else {
                                $this->logger->error('Schedule\\Task\\Executor: Version [ '.$task->getVersion().' ] does not exist.');
                            }
                        } elseif ($task->getAction() === 'publish' && $object->isAllowed('publish', $taskUser)) {
                            $object->setPublished(true);
                            $object->save();
                        } elseif ($task->getAction() === 'unpublish' && $object->isAllowed('unpublish', $taskUser)) {
                            $object->setPublished(false);
                            $object->save();
                        } elseif ($task->getAction() === 'delete' && $object->isAllowed('delete', $taskUser)) {
                            $object->delete();
                        }
                    }
                }

                $task->setActive(false);
                $task->save();
            } catch (Exception $e) {
                $this->logger->error('There was a problem with the scheduled task ID: '.$task->getId());
                $this->logger->error((string) $e);
            }
        }
    }
}
