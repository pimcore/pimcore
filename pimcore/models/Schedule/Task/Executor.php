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
 * @category   Pimcore
 * @package    Schedule
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Schedule\Task;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Version;

class Executor
{
    public static function execute()
    {
        $list = new Listing();
        $list->setCondition('active = 1 AND date < ?', time());
        $tasks = $list->load();

        foreach ($tasks as $task) {
            try {
                if ($task->getCtype() == 'document') {
                    $document = Document::getById($task->getCid());
                    if ($document instanceof Document) {
                        if ($task->getAction() == 'publish-version' && $task->getVersion()) {
                            try {
                                $version = Version::getById($task->getVersion());
                                $document = $version->getData();
                                if ($document instanceof Document) {
                                    $document->setPublished(true);
                                    $document->save();
                                } else {
                                    Logger::err('Schedule\\Task\\Executor: Could not restore document from version data.');
                                }
                            } catch (\Exception $e) {
                                Logger::err('Schedule\\Task\\Executor: Version [ '.$task->getVersion().' ] does not exist.');
                            }
                        } elseif ($task->getAction() == 'publish') {
                            $document->setPublished(true);
                            $document->save();
                        } elseif ($task->getAction() == 'unpublish') {
                            $document->setPublished(false);
                            $document->save();
                        } elseif ($task->getAction() == 'delete') {
                            $document->delete();
                        }
                    }
                } elseif ($task->getCtype() == 'asset') {
                    $asset = Asset::getById($task->getCid());

                    if ($asset instanceof Asset) {
                        if ($task->getAction() == 'publish-version' && $task->getVersion()) {
                            try {
                                $version = Version::getById($task->getVersion());
                                $asset = $version->getData();
                                if ($asset instanceof Asset) {
                                    $asset->save();
                                } else {
                                    Logger::err('Schedule\\Task\\Executor: Could not restore asset from version data.');
                                }
                            } catch (\Exception $e) {
                                Logger::err('Schedule\\Task\\Executor: Version [ '.$task->getVersion().' ] does not exist.');
                            }
                        } elseif ($task->getAction() == 'delete') {
                            $asset->delete();
                        }
                    }
                } elseif ($task->getCtype() == 'object') {
                    $object = DataObject::getById($task->getCid());

                    if ($object instanceof DataObject) {
                        if ($task->getAction() == 'publish-version' && $task->getVersion()) {
                            try {
                                $version = Version::getById($task->getVersion());
                                $object = $version->getData();
                                if ($object instanceof DataObject\AbstractObject) {
                                    $object->setPublished(true);
                                    $object->save();
                                } else {
                                    Logger::err('Schedule\\Task\\Executor: Could not restore object from version data.');
                                }
                            } catch (\Exception $e) {
                                Logger::err('Schedule\\Task\\Executor: Version [ '.$task->getVersion().' ] does not exist.');
                            }
                        } elseif ($task->getAction() == 'publish') {
                            $object->setPublished(true);
                            $object->save();
                        } elseif ($task->getAction() == 'unpublish') {
                            $object->setPublished(false);
                            $object->save();
                        } elseif ($task->getAction() == 'delete') {
                            $object->delete();
                        }
                    }
                }

                $task->setActive(false);
                $task->save();
            } catch (\Exception $e) {
                Logger::err('There was a problem with the scheduled task ID: ' . $task->getId());
                Logger::err($e);
            }
        }
    }
}
