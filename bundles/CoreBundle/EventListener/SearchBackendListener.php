<?php

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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Messenger\SearchBackendMessage;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
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
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPostAddUpdateElement(ElementEventInterface $e)
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

    /**
     * @param ElementEventInterface $e
     */
    public function onPreDeleteElement(ElementEventInterface $e)
    {
        $searchEntry = Data::getForElement($e->getElement());
        if ($searchEntry instanceof Data && $searchEntry->getId() instanceof Data\Id) {
            $searchEntry->delete();
        }
    }
}
