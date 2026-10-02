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

use Exception;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Schedule\Task;
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
                            if ($version = $this->getOwnedVersion($task)) {
                                $document = $version->getData();
                                if ($document instanceof Document) {
                                    $document->setPublished(true);
                                    $document->save();
                                } else {
                                    $this->logger->error('Schedule\\Task\\Executor: Could not restore document from version data.');
                                }
                            }
                        } elseif ($task->getAction() === 'publish' && $document->isAllowed('publish', $taskUser)) {
                            $document->setPublished(true);
                            $document->save();
                        } elseif ($task->getAction() === 'unpublish' && $document->isAllowed('unpublish', $taskUser)) {
                            $document->setPublished(false);
                            $document->save();
                        } elseif ($task->getAction() === 'delete' && $document->isAllowed('delete', $taskUser)) {
                            Recyclebin\Item::create($document);
                            $document->delete();
                        }
                    }
                } elseif ($task->getCtype() === 'asset') {
                    $asset = Asset::getById($task->getCid());

                    if ($asset instanceof Asset) {
                        if ($task->getAction() === 'publish-version' && $task->getVersion() && $asset->isAllowed('publish', $taskUser) && $asset->isAllowed('versions', $taskUser)) {
                            if ($version = $this->getOwnedVersion($task)) {
                                $asset = $version->getData();
                                if ($asset instanceof Asset) {
                                    $asset->save();
                                } else {
                                    $this->logger->error('Schedule\\Task\\Executor: Could not restore asset from version data.');
                                }
                            }
                        } elseif ($task->getAction() === 'delete' && $asset->isAllowed('delete', $taskUser)) {
                            Recyclebin\Item::create($asset);
                            $asset->delete();
                        }
                    }
                } elseif ($task->getCtype() === 'object') {
                    $object = DataObject::getById($task->getCid());

                    if ($object instanceof DataObject\Concrete) {
                        if ($task->getAction() === 'publish-version' && $task->getVersion() && $object->isAllowed('publish', $taskUser) && $object->isAllowed('versions', $taskUser)) {
                            if ($version = $this->getOwnedVersion($task)) {
                                $object = $version->getData();
                                if ($object instanceof DataObject\Concrete) {
                                    $object->setPublished(true);
                                    $object->save();
                                } else {
                                    $this->logger->error('Schedule\\Task\\Executor: Could not restore object from version data.');
                                }
                            }
                        } elseif ($task->getAction() === 'publish' && $object->isAllowed('publish', $taskUser)) {
                            $object->setPublished(true);
                            $object->save();
                        } elseif ($task->getAction() === 'unpublish' && $object->isAllowed('unpublish', $taskUser)) {
                            $object->setPublished(false);
                            $object->save();
                        } elseif ($task->getAction() === 'delete' && $object->isAllowed('delete', $taskUser)) {
                            Recyclebin\Item::create($object);
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

    /**
     * Returns the version referenced by the task, but only if it belongs to the task's own element.
     * A mismatch is rejected (and logged) before the version data is unserialized, so a task can
     * never restore and overwrite another element (GHSA-mfr4-5hr5-jqvg).
     */
    private function getOwnedVersion(Task $task): ?Version
    {
        $version = Version::getById($task->getVersion());
        if (!$version) {
            $this->logger->error('Schedule\\Task\\Executor: Version [ '.$task->getVersion().' ] does not exist.');

            return null;
        }

        if ($version->getCid() !== $task->getCid() || $version->getCtype() !== $task->getCtype()) {
            $this->logger->error(
                'Schedule\\Task\\Executor: Version [ '.$task->getVersion().
                ' ] does not belong to element [ '.$task->getCid().' ].'
            );

            return null;
        }

        return $version;
    }
}
