<?php

namespace Pimcore\Model\Paginator\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Pimcore\Model\Paginator\Adapter\AdapterInterface;
use Pimcore\Model\Paginator\AdapterAggregateInterface;

class PaginateListingSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $paginationAdapter = $event->target;
        if ($paginationAdapter instanceof AdapterAggregateInterface) {
            $paginationAdapter = $event->target->getPaginatorAdapter();
        }

        if ($paginationAdapter instanceof AdapterInterface) {
            $event->count = count($paginationAdapter);
            $items = $paginationAdapter->getItems($event->getOffset(), $event->getLimit());
            $event->items = $items;
            $event->stopPropagation();
        }

        if (!$event->isPropagationStopped()) {
            throw new \RuntimeException('Paginator accepts instances of the type ' .
                'Pimcore\Model\Paginator\Adapter\AdapterInterface or Pimcore\Model\Paginator\AdapterAggregateInterface or types defined here: https://github.com/KnpLabs/KnpPaginatorBundle#controller');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 2]
        ];
    }
}
