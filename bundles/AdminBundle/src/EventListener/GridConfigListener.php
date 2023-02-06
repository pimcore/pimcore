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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Db;
use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\UserRoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class GridConfigListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectClassDefinitionEvents::POST_DELETE => 'onClassDelete',
            UserRoleEvents::POST_DELETE => 'onUserDelete',
            DataObjectEvents::POST_DELETE => 'onObjectDelete',
        ];
    }

    public function onObjectDelete(DataObjectEvent $event): void
    {
        $object = $event->getObject();
        $objectId = $object->getId();

        $this->cleanupGridConfigFavourites('objectId = ' . $objectId);
    }

    public function onClassDelete(ClassDefinitionEvent $event): void
    {
        $class = $event->getClassDefinition();
        $classId = $class->getId();

        // collect gridConfigs for that class id
        $db = Db::get();
        $gridConfigIds = $db->fetchFirstColumn('select id from gridconfigs where classId = ?', [$classId]);
        if ($gridConfigIds) {
            $db->executeQuery('delete from gridconfig_shares where gridConfigId in (' . implode($gridConfigIds) . ')');
        }

        $this->cleanupGridConfigs('classId = ' . $db->quote($classId));
        $this->cleanupGridConfigFavourites('classId = ' . $db->quote($classId));
    }

    public function onUserDelete(UserRoleEvent $event): void
    {
        $user = $event->getUserRole();
        $userId = $user->getId();

        $db = Db::get();

        $gridConfigIds = $db->fetchFirstColumn('select id from gridconfigs where ownerId = ' . $userId);
        if ($gridConfigIds) {
            $db->executeQuery('delete from gridconfig_shares where gridConfigId in (' . implode($gridConfigIds) . ')');
        }

        $this->cleanupGridConfigs('ownerId = ' . $userId);
        $this->cleanupGridConfigFavourites('ownerId = ' . $userId);
    }

    protected function cleanupGridConfigs(string $condition): void
    {
        $db = Db::get();
        $db->executeQuery('DELETE FROM gridconfigs where ' . $condition);
    }

    protected function cleanupGridConfigFavourites(string $condition): void
    {
        $db = Db::get();
        $db->executeQuery('DELETE FROM gridconfig_favourites where ' . $condition);
    }
}
