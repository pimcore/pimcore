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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Log\FileObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Tool\Admin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ObjectDefinitionListener
 *
 * Adds ApplicationLogger lines with the events (Create, Update, Delete) on classes.
 *
 * @package Pimcore\Bundle\AdminBundle\EventListener
 */
class ObjectDefinitionListener implements EventSubscriberInterface
{
    public const LOGGER_CLASSES = 'dataobject-classes';

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectClassDefinitionEvents::POST_ADD => 'onDefinitionAdd',
            DataObjectClassDefinitionEvents::POST_UPDATE => 'onDefinitionUpdate',
            DataObjectClassDefinitionEvents::POST_DELETE => 'onDefinitionDelete',
        ];
    }

    /**
     * @param ClassDefinitionEvent $event
     */
    public function onDefinitionAdd(ClassDefinitionEvent $event): void
    {
        $class = $event->getClassDefinition();
        $logger = ApplicationLogger::getInstance(self::LOGGER_CLASSES, true);
        $logger->info("{$this->getCurrentUser()} added class definition for {$class->getName()} (id:{$class->getId()})", [
            'fileObject' => new FileObject(ClassDefinition\Service::generateClassDefinitionJson($class)),
        ]);
    }

    /**
     * @param ClassDefinitionEvent $event
     */
    public function onDefinitionUpdate(ClassDefinitionEvent $event): void
    {
        $class = $event->getClassDefinition();
        $logger = ApplicationLogger::getInstance(self::LOGGER_CLASSES, true);
        $logger->info("{$this->getCurrentUser()} updated class definition for {$class->getName()} (id:{$class->getId()})", [
            'fileObject' => new FileObject(ClassDefinition\Service::generateClassDefinitionJson($class)),
        ]);
    }

    /**
     * @param ClassDefinitionEvent $event
     */
    public function onDefinitionDelete(ClassDefinitionEvent $event): void
    {
        $class = $event->getClassDefinition();
        $logger = ApplicationLogger::getInstance(self::LOGGER_CLASSES, true);
        $logger->info("{$this->getCurrentUser()} deleted class definition for {$class->getName()} (id:{$class->getId()})");
    }

    /**
     * Returns the current user triggering the event, or a dummy "System" user if no user triggered it, such as during
     * an installation, or Composer update, or Test.
     *
     * @return string
     */
    protected function getCurrentUser()
    {
        $user = Admin::getCurrentUser();
        if ($user instanceof Pimcore\Model\User) {
            return "{$user->getName()} (id:{$user->getId()})";
        }

        return 'System';
    }
}
