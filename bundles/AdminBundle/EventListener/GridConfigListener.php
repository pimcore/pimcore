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

use Pimcore\Db;
use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\UserRoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GridConfigListener implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectClassDefinitionEvents::POST_DELETE => 'onClassDelete',
            UserRoleEvents::POST_DELETE => 'onUserDelete',
            DataObjectEvents::POST_DELETE => 'onObjectDelete',
        ];
    }

    /**
     * @param DataObjectEvent $event
     */
    public function onObjectDelete($event)
    {
        $object = $event->getObject();
        $objectId = $object->getId();

        $this->cleanupGridConfigFavourites('objectId = ' . $objectId);
    }

    /**
     * @param ClassDefinitionEvent $event
     */
    public function onClassDelete($event)
    {
        $class = $event->getClassDefinition();
        $classId = $class->getId();

        // collect gridConfigs for that class id
        $db = Db::get();
        $gridConfigIds = $db->fetchCol('select id from gridconfigs where classId = ?', $classId);
        if ($gridConfigIds) {
            $db->query('delete from gridconfig_shares where gridConfigId in (' . implode($gridConfigIds) . ')');
        }

        $this->cleanupGridConfigs('classId = ' . $db->quote($classId));
        $this->cleanupGridConfigFavourites('classId = ' . $db->quote($classId));
    }

    /**
     * @param UserRoleEvent $event
     */
    public function onUserDelete($event)
    {
        $user = $event->getUserRole();
        $userId = $user->getId();

        $db = Db::get();

        $gridConfigIds = $db->fetchCol('select id from gridconfigs where ownerId = ' . $userId);
        if ($gridConfigIds) {
            $db->query('delete from gridconfig_shares where gridConfigId in (' . implode($gridConfigIds) . ')');
        }

        $this->cleanupGridConfigs('ownerId = ' . $userId);
        $this->cleanupGridConfigFavourites('ownerId = ' . $userId);
    }

    protected function cleanupGridConfigs($condition)
    {
        $db = Db::get();
        $db->query('DELETE FROM gridconfigs where ' . $condition);
    }

    protected function cleanupGridConfigFavourites($condition)
    {
        $db = Db::get();
        $db->query('DELETE FROM gridconfig_favourites where ' . $condition);
    }
}
