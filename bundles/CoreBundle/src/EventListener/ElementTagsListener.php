<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Element\Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class ElementTagsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_COPY => 'onPostCopy',
            DocumentEvents::POST_COPY => 'onPostCopy',
            AssetEvents::POST_COPY => 'onPostCopy',

            AssetEvents::POST_DELETE => ['onPostAssetDelete', -9999],
        ];
    }

    public function onPostCopy(ElementEventInterface $e): void
    {
        $elementType = Service::getElementType($e->getElement());
        $copiedElement = $e->getElement();
        /** @var \Pimcore\Model\Element\ElementInterface $baseElement */
        $baseElement = $e->getArgument('base_element');
        \Pimcore\Model\Element\Tag::setTagsForElement(
            $elementType,
            $copiedElement->getId(),
            \Pimcore\Model\Element\Tag::getTagsForElement($elementType, $baseElement->getId())
        );
    }

    public function onPostAssetDelete(AssetEvent $e): void
    {
        $asset = $e->getAsset();
        \Pimcore\Model\Element\Tag::setTagsForElement('asset', $asset->getId(), []);
    }
}
