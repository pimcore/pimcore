<?php

namespace Pimcore\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatch($event/*, $eventName = null*/) {
        $eventName = 1 < \func_num_args() ? func_get_arg(1) : null;
        if(\version_compare(Kernel::VERSION, '4.3') >= 0) {
            if(is_string($event) && is_object($eventName)) {
                $tmp = $eventName;
                $eventName = $event;
                $event = $tmp;
            }
            $this->eventDispatcher->dispatch($event, $eventName);
        } else {
            if(is_string($eventName) && is_object($event)) {
                $tmp = $eventName;
                $eventName = $event;
                $event = $tmp;
            }
            $this->eventDispatcher->dispatch($eventName, $event);
        }
    }

    public function addListener($eventName, $listener, $priority = 0)
    {
        return $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->eventDispatcher->addSubscriber($subscriber);
    }

    public function removeListener($eventName, $listener)
    {
        return $this->eventDispatcher->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->eventDispatcher->removeSubscriber($subscriber);
    }

    public function getListeners($eventName = null)
    {
        return $this->eventDispatcher->getListeners($eventName);
    }

    public function getListenerPriority($eventName, $listener)
    {
        return $this->eventDispatcher->getListenerPriority($eventName, $listener);
    }

    public function hasListeners($eventName = null)
    {
        return $this->eventDispatcher->hasListeners($eventName);
    }

    public function __call($method, $args)
    {
        if (is_callable([$this->eventDispatcher, $method])) {
            return call_user_func_array(array($this->eventDispatcher, $method), $args);
        }
        throw new \Exception(
            'Undefined method - ' . get_class($this->eventDispatcher) . '::' . $method
        );
    }

    public function __get($property)
    {
        if (property_exists($this->eventDispatcher, $property)) {
            return $this->eventDispatcher->$property;
        }
        return null;
    }

    public function __isset($property)
    {
        return isset($this->eventDispatcher->$property);
    }

    public function __set($property, $value)
    {
        $this->eventDispatcher->$property = $value;
        return $this;
    }
}
