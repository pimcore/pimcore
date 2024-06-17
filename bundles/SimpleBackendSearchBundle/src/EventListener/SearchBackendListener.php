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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\EventListener;

use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Bundle\SimpleBackendSearchBundle\Message\SearchBackendMessage;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\DataObject\Listing;
use Pimcore\Model\Element\Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
class SearchBackendListener implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messengerBusPimcoreCore
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        $events = [
            DataObjectEvents::POST_ADD => 'onPostAddUpdateElement',
            DocumentEvents::POST_ADD => 'onPostAddUpdateElement',
            AssetEvents::POST_ADD => 'onPostAddUpdateElement',

            DataObjectEvents::PRE_DELETE => 'onPreDeleteElement',
            DocumentEvents::PRE_DELETE => 'onPreDeleteElement',
            AssetEvents::PRE_DELETE => 'onPreDeleteElement',

            DataObjectEvents::POST_UPDATE => 'onPostAddUpdateElement',
            DocumentEvents::POST_UPDATE => 'onPostAddUpdateElement',
            AssetEvents::POST_UPDATE => 'onPostAddUpdateElement',
        ];

        // used when admin UI classic bundle is installed
        if (class_exists(AdminEvents::class) && defined(AdminEvents::class . '::OBJECT_LIST_HANDLE_FULLTEXT_QUERY')) {
            $events[AdminEvents::OBJECT_LIST_HANDLE_FULLTEXT_QUERY] = 'onHandleFulltextQuery';
        }

        return $events;
    }

    public function onPostAddUpdateElement(ElementEventInterface $e): void
    {
        //do not update index when auto save or only saving version
        if (
            !$e instanceof AssetEvent &&
            (($e->hasArgument('isAutoSave') && $e->getArgument('isAutoSave')) ||
                ($e->hasArgument('saveVersionOnly') && $e->getArgument('saveVersionOnly')))
        ) {
            return;
        }

        $element = $e->getElement();
        $this->messengerBusPimcoreCore->dispatch(
            new SearchBackendMessage(Service::getElementType($element), $element->getId())
        );
    }

    public function onPreDeleteElement(ElementEventInterface $e): void
    {
        $searchEntry = Data::getForElement($e->getElement());
        if ($searchEntry instanceof Data && $searchEntry->getId() instanceof Data\Id) {
            $searchEntry->delete();
        }
    }

    public function onHandleFulltextQuery(GenericEvent $e): void
    {
        $query = $e->getArgument('query');
        /** @var Listing $list */
        $list = $e->getArgument('list');
        $e->setArgument(
            'condition',
            'oo_id IN (SELECT id FROM search_backend_data WHERE maintype = "object" AND MATCH (`data`,`properties`) AGAINST (' . $list->quote($query) . ' IN BOOLEAN MODE))'
        );
    }
}
