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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\EventListener;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Installer;
use Pimcore\Event\System\MaintenanceEvent;
use Pimcore\Event\SystemEvents;
use Pimcore\Model\Schedule\Maintenance\Job;
use Pimcore\Tool\ClassUtils;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemEventsListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::MAINTENANCE => 'onMaintenance',
        ];
    }

    public function onMaintenance(MaintenanceEvent $event)
    {
        // register maintenance as job instead of running it directly as otherwise
        // the filtering methods on maintenance command won't work
        $jobName = ClassUtils::getBaseName(PimcoreEcommerceFrameworkBundle::class);

        $event->getManager()->registerJob(Job::fromClosure($jobName, function () {
            $this->handleMaintenance();
        }));
    }

    private function handleMaintenance()
    {
        // fetch installer only on demand
        $installer = $this->container->get(Installer::class);
        if (!$installer->isInstalled()) {
            return;
        }

        $checkoutManager = Factory::getInstance()->getCheckoutManager(new Cart());
        $checkoutManager->cleanUpPendingOrders();

        Factory::getInstance()->getVoucherService()->cleanUpReservations();
        Factory::getInstance()->getVoucherService()->cleanUpStatistics();
    }
}
