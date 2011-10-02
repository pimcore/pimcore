<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Schedule_Task_Executor {

    public static function execute() {

        $list = new Schedule_Task_List();
        $list->setCondition("active = 1 AND date < ?", time());
        $tasks = $list->load();

        foreach ($tasks as $task) {

            try {

                if ($task->getCtype() == "document") {

                    $document = Document::getById($task->getCid());


                    if ($document instanceof Document) {
                        if ($task->getAction() == "publish-version" && $task->getVersion()) {
                            try{
                                $version = Version::getById($task->getVersion());
                                $document = $version->getData();
                                if($document instanceof Document){
                                    $document->setPublished(true);
                                    $document->save();
                                } else {
                                    Logger::err("Schedule_Task_Executor: Could not restore document from version data.");
                                }

                            } catch(Exception $e){
                                Logger::err("Schedule_Task_Executor: Version [ ".$task->getVersion()." ] does not exist.");
                            }

                        }
                        else if ($task->getAction() == "publish") {
                            $document->setPublished(true);
                            $document->save();
                        }
                        else if ($task->getAction() == "unpublish") {
                            $document->setPublished(false);
                            $document->save();
                        }
                        else if ($task->getAction() == "delete") {
                            $document->delete();
                        }
                    }
                }
                else if ($task->getCtype() == "asset") {

                    $asset = Asset::getById($task->getCid());

                    if ($asset instanceof Asset) {
                        if ($task->getAction() == "publish-version" && $task->getVersion()) {
                            try{
                                $version = Version::getById($task->getVersion());
                                $asset = $version->getData();
                                if($asset instanceof Asset){
                                    $asset->save();
                                } else {
                                    Logger::err("Schedule_Task_Executor: Could not restore asset from version data.");
                                }

                            } catch(Exception $e){
                                Logger::err("Schedule_Task_Executor: Version [ ".$task->getVersion()." ] does not exist.");
                            }
                        }
                        else if ($task->getAction() == "delete") {
                            $asset->delete();
                        }
                    }
                }
                else if ($task->getCtype() == "object") {

                    $object = Object_Abstract::getById($task->getCid());

                    if ($object instanceof Object_Abstract) {
                        if ($task->getAction() == "publish-version" && $task->getVersion()) {
                            try{
                                $version = Version::getById($task->getVersion());
                                $object = $version->getData();
                                if($object instanceof Object_Abstract){
                                    $object->setO_Published(true);
                                    $object->save();
                                } else {
                                    Logger::err("Schedule_Task_Executor: Could not restore object from version data.");
                                }

                            } catch(Exception $e){
                                Logger::err("Schedule_Task_Executor: Version [ ".$task->getVersion()." ] does not exist.");
                            }
                        }
                        else if ($task->getAction() == "publish") {
                            $object->setO_Published(true);
                            $object->save();
                        }
                        else if ($task->getAction() == "unpublish") {
                            $object->setO_Published(false);
                            $object->save();
                        }
                        else if ($task->getAction() == "delete") {
                            $object->delete();
                        }
                    }
                }

                $task->setActive(false);
                $task->save();
                
            } catch (Exception $e) {
                Logger::err("There was a problem with the scheduled task ID: " . $task->getId());
                Logger::err($e);
            }
        }
    }
}
