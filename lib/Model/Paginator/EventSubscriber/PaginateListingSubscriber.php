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

namespace Pimcore\Model\Paginator\EventSubscriber;

use Knp\Component\Pager\Event\ItemsEvent;
use Pimcore\Model\Paginator\PaginateListingInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaginateListingSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event): void
    {
        $paginationAdapter = $event->target;

        if ($paginationAdapter instanceof PaginateListingInterface) {
            $items = $paginationAdapter->getItems($event->getOffset(), $event->getLimit());
            $event->count = $paginationAdapter->count();
            $event->items = $items;
            $event->stopPropagation();
        }

        if (!$event->isPropagationStopped()) {
            throw new RuntimeException('Paginator only accepts instances of the type ' .
                PaginateListingInterface::class . ' or types defined here: https://github.com/KnpLabs/KnpPaginatorBundle#controller');
        }
    }

    /**
     * @internal
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', -5/* other data listeners should be analyzed first*/],
        ];
    }
}
