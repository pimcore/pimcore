<?php

namespace Pimcore\Legacy;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\Object\ClassDefinitionEvent;
use Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent;
use Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent;
use Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent;
use Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent;
use Pimcore\Event\Model\Object\CustomLayoutEvent;
use Pimcore\Event\Model\SearchBackendEvent;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\Model\WorkflowEvent;
use Pimcore\Event\SystemEvents;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Component\EventDispatcher\GenericEvent;

class EventManager extends \Zend_EventManager_EventManager {

    public function attach($event, $callback = null, $priority = 1)
    {
        $self = $this;
        $eventName = $event;
        $newEventName = "pimcore." . $eventName;

        $listenerMappings = [
            "system.di.init" => SystemEvents::PHP_DI_INIT,
            "system.maintenance.activate" => SystemEvents::MAINTENANCE_MODE_ACTIVATE,
            "system.maintenance.deactivate" => SystemEvents::MAINTENANCE_MODE_DEACTIVATE,
            "system.cache.clearOutputCache" => SystemEvents::CACHE_CLEAR_FULLPAGE_CACHE
        ];

        if(isset($listenerMappings[$eventName])) {
            $newEventName = $listenerMappings[$eventName];
        }

        \Pimcore::getEventDispatcher()->addListener($newEventName, function ($event) use ($self, $eventName) {

            $target = null;
            $params = [];

            if($event instanceof ElementEventInterface) {
                $target = $event->getElement();
            }

            if($event instanceof SearchBackendEvent) {
                $target = $event->getData();
            }

            if($event instanceof UserRoleEvent) {
                $target = $event->getUserRole();
            }

            if($event instanceof VersionEvent) {
                $target = $event->getVersion();
            }

            if($event instanceof WorkflowEvent) {
                $target = $event->getWorkflowManager();
            }

            if($event instanceof CollectionConfigEvent) {
                $target = $event->getCollectionConfig();
            }

            if($event instanceof GroupConfigEvent) {
                $target = $event->getGroupConfig();
            }

            if($event instanceof KeyConfigEvent) {
                $target = $event->getKeyConfig();
            }

            if($event instanceof StoreConfigEvent) {
                $target = $event->getStoreConfig();
            }

            if($event instanceof ClassDefinitionEvent) {
                $target = $event->getClassDefinition();
            }

            if($event instanceof CustomLayoutEvent) {
                $target = $event->getCustomLayout();
            }

            if($event instanceof ArgumentsAwareTrait) {
                $params = $event->getArguments();
            }

            if($event instanceof GenericEvent) {
                $target = $event->getSubject();
                $params = $event->getArguments();
            }

            // add the Symfony event for debugging purposes
            $params["__SYMFONY_EVENT"] = $event;

            $returnValueContainerMappings = [
                "admin.document.get.preSendData" => [
                    "param" => "returnValueContainer",
                    "argument" => "data"
                ],
                "admin.asset.get.preSendData" => [
                    "param" => "returnValueContainer",
                    "argument" => "data"
                ],
                "admin.class.objectbrickList.preSendData" => [
                    "param" => "returnValueContainer",
                    "argument" => "list"
                ],
                "admin.object.treeGetChildsById.preSendData" => [
                    "param" => "returnValueContainer",
                    "argument" => "objects"
                ],
                "admin.object.get.preSendData" => [
                    "param" => "returnValueContainer",
                    "argument" => "data"
                ],
                "admin.search.list.beforeFilterPrepare" => [
                    "param" => "requestParams",
                    "argument" => "requestParams"
                ],
                "admin.search.list.beforeListLoad" => [
                    "param" => "list",
                    "argument" => "data"
                ],
                "admin.search.list.afterListLoad" => [
                    "param" => "list",
                    "argument" => "data"
                ],
            ];

            $isUsingReturnValueContainer = false;
            $returnValueContainer = null;
            if(isset($returnValueContainerMappings[$eventName]) && $event instanceof GenericEvent) {
                if($event->hasArgument($returnValueContainerMappings[$eventName]["argument"])) {
                    $dataFromArgument = $event->getArgument($returnValueContainerMappings[$eventName]["argument"]);
                    $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer($dataFromArgument);
                    $isUsingReturnValueContainer = true;

                    $params[$returnValueContainerMappings[$eventName]["param"]] = $returnValueContainer;
                }
            }

            $self->trigger($eventName, $target, $params);

            if($isUsingReturnValueContainer) {
                $event->setArgument($returnValueContainerMappings[$eventName]["argument"], $returnValueContainer->getData());
            }
        });


        return parent::attach($event, $callback, $priority);
    }
}
