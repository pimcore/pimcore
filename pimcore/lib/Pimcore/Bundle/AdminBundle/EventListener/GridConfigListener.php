<?php

declare(strict_types=1);

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


class GridConfigListener implements EventSubscriberInterface
{

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectClassDefinitionEvents::POST_DELETE => 'onClassDelete',
            UserRoleEvents::POST_DELETE => "onUserDelete"
        ];
    }

    /**
     * @param $event ClassDefinitionEvent
     */
    public function onClassDelete($event) {
        $class = $event->getClassDefinition();
        $classId = $class->getId();
        $this->cleanupGridConfigs("classId = " . $classId);
    }

    /**
     * @param $event UserRoleEvent
     */
    public function onUserDelete($event) {
        $user = $event->getUserRole();
        $userId = $user->getId();
        $this->cleanupGridConfigs("ownerId = " . $userId);
    }

    protected function cleanupGridConfigs($condition) {
        $db = Db::get();
        $db->query("DELETE FROM gridconfigs where " . $condition);
    }

}
