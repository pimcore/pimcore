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

namespace Pimcore\Targeting\EventListener;

use Pimcore\Event\System\MaintenanceEvent;
use Pimcore\Event\SystemEvents;
use Pimcore\Model\Schedule\Maintenance\Job;
use Pimcore\Targeting\Storage\MaintenanceStorageInterface;
use Pimcore\Targeting\Storage\TargetingStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MaintenanceListener implements EventSubscriberInterface
{
    /**
     * @var TargetingStorageInterface|MaintenanceStorageInterface
     */
    private $targetingStorage;

    public function __construct(TargetingStorageInterface $targetingStorage)
    {
        $this->targetingStorage = $targetingStorage;
    }

    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::MAINTENANCE => 'onPimcoreMaintenance'
        ];
    }

    public function onPimcoreMaintenance(MaintenanceEvent $event)
    {
        if (!$this->targetingStorage instanceof MaintenanceStorageInterface) {
            return;
        }

        $event->getManager()->registerJob(Job::fromClosure('targetingMaintenance', function () {
            $this->targetingStorage->maintenance();
        }));
    }
}
