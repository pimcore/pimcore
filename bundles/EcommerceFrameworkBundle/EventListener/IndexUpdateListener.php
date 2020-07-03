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
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IndexUpdateListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::POST_ADD => 'onObjectUpdate',
            DataObjectEvents::POST_UPDATE => 'onObjectUpdate',
            DataObjectEvents::PRE_DELETE => 'onObjectDelete',
        ];
    }

    public function onObjectUpdate(DataObjectEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof IndexableInterface && (!$event->hasArgument('saveVersionOnly') || !$event->getArgument('saveVersionOnly'))) {
            $indexService = Factory::getInstance()->getIndexService();
            $indexService->updateIndex($object);
        }
    }

    public function onObjectDelete(DataObjectEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof IndexableInterface) {
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
