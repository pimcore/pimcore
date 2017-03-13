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

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\ObjectEvents;
use Pimcore\Model\Element\Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ElementTagsListener implements EventSubscriberInterface {

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ObjectEvents::POST_COPY  => 'onPostCopy',
            DocumentEvents::POST_COPY  => 'onPostCopy',
            AssetEvents::POST_COPY  => 'onPostCopy',

            AssetEvents::POST_DELETE => ['onPostAssetDelete', -9999]
        ];
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPostCopy(ElementEventInterface $e) {
        $elementType = Service::getElementType($e->getElement());
        /** @var \Pimcore\Model\Element\AbstractElement $copiedElement */
        $copiedElement = $e->getElement();
        /** @var \Pimcore\Model\Element\AbstractElement $baseElement */
        $baseElement = $e->getAttribute('base_element');
        \Pimcore\Model\Element\Tag::setTagsForElement($elementType, $copiedElement->getId(),
            \Pimcore\Model\Element\Tag::getTagsForElement($elementType, $baseElement->getId()));
    }

    /**
     * @param AssetEvent $e
     */
    public function onPostAssetDelete(AssetEvent $e) {
        $asset = $e->getAsset();
        \Pimcore\Model\Element\Tag::setTagsForElement("asset", $asset->getId(), []);
    }
}
