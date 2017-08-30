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

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Event\Model\ObjectEvent;
use Pimcore\Event\ObjectEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IndexUpdateListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ObjectEvents::PRE_ADD => 'onObjectUpdate',
            ObjectEvents::POST_UPDATE => 'onObjectUpdate',
            ObjectEvents::PRE_DELETE => 'onObjectDelete'
        ];
    }

    public function onObjectUpdate(ObjectEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof IIndexable) {
            $indexService = Factory::getInstance()->getIndexService();
            $indexService->updateIndex($object);
        }
    }

    public function onObjectDelete(ObjectEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof IIndexable) {
            $indexService = Factory::getInstance()->getIndexService();
            $indexService->deleteFromIndex($object);
        }

        // Delete tokens when a a configuration object gets removed.
        if ($object instanceof \Pimcore\Model\DataObject\OnlineShopVoucherSeries) {
            $voucherService = Factory::getInstance()->getVoucherService();
            $voucherService->cleanUpVoucherSeries($object);
        }
    }
}
