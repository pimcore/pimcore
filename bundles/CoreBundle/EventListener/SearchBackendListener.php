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
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::POST_ADD => 'onPostAddElement',
            DocumentEvents::POST_ADD => 'onPostAddElement',
            AssetEvents::POST_ADD => 'onPostAddElement',

            DataObjectEvents::PRE_DELETE => 'onPreDeleteElement',
            DocumentEvents::PRE_DELETE => 'onPreDeleteElement',
            AssetEvents::PRE_DELETE => 'onPreDeleteElement',

            DataObjectEvents::POST_UPDATE => 'onPostUpdateElement',
            DocumentEvents::POST_UPDATE => 'onPostUpdateElement',
            AssetEvents::POST_UPDATE => 'onPostUpdateElement',
        ];
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPostAddElement(ElementEventInterface $e)
    {
        $element = $e->getElement();
        $this->messageBus->dispatch(
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

    /**
     * @param ElementEventInterface $e
     */
    public function onPostUpdateElement(ElementEventInterface $e)
    {
        $element = $e->getElement();
        $this->messageBus->dispatch(
            new SearchBackendMessage(Service::getElementType($element), $element->getId())
        );
    }
}
