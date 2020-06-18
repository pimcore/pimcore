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
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\UserRoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImportConfigListener implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectClassDefinitionEvents::POST_DELETE => 'onClassDelete',
            UserRoleEvents::POST_DELETE => 'onUserDelete',
        ];
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
        $importConfigIds = $db->fetchCol('select id from importconfigs where classId = ?', $classId);
        if ($importConfigIds) {
            $db->query('delete from importconfig_shares where importConfigId in (' . implode($importConfigIds) . ')');
        }

        $this->cleanupImportConfigs('classId = ' . $db->quote($classId));
    }

    /**
     * @param UserRoleEvent $event
     */
    public function onUserDelete($event)
    {
        $user = $event->getUserRole();
        $userId = $user->getId();

        $db = Db::get();

        $importConfigIds = $db->fetchCol('select id from importconfigs where ownerId = ' . $userId);
        if ($importConfigIds) {
            $db->query('delete from importconfig_shares where importConfigId in (' . implode($importConfigIds) . ')');
        }

        $this->cleanupImportConfigs('ownerId = ' . $userId);
    }

    protected function cleanupImportConfigs($condition)
    {
        $db = Db::get();
        $db->query('DELETE FROM importconfigs where ' . $condition);
    }
}
