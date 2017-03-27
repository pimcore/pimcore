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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\EventListener;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\Cart;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Event\System\ConsoleEvent;
use Pimcore\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemEventsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::MAINTENANCE => 'onMaintenance',
            SystemEvents::CONSOLE_INIT => 'onConsoleInit'
        ];
    }


    public function onConsoleInit(ConsoleEvent $event)
    {
        $application = $event->getApplication();

        // add a namespace to autoload commands from
        $application->addAutoloadNamespace(
            'Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Console\Command', __DIR__ . '/../Console/Command'
        );
    }


    public function onMaintenance()
    {
        $checkoutManager = Factory::getInstance()->getCheckoutManager(new Cart());
        $checkoutManager->cleanUpPendingOrders();

        Factory::getInstance()->getVoucherService()->cleanUpReservations();
        Factory::getInstance()->getVoucherService()->cleanUpStatistics();
    }
}
