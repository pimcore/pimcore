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
 *  @license    http://www.pimcore.org/license GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\EventListener;


use Pimcore\Event\AssetEvents;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Search\Backend\Data;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Event\Model\AssetEvent;
/**
 * @internal
 */
class AssetFulltextSearchUpdateListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;


    public function onSaveAfter(AssetEvent $event)
    {
        $asset = $event->getAsset();
        if (!$asset instanceof Folder) {
            return;
        }

        // Update full path for the folder
        $children = $asset->getChildren();
        $data = Data::getForElement($asset);
        $data->setFullPath($asset->getFullPath());
        $data->setData($asset->getData());
        $asset->save();

        // Update full path for children
        foreach($children as $child) {
            $data = Data::getForElement($child);
            $data->setFullPath($child->getFullPath());
            $data->setData($child->getData());
            $data->save();
        }

    }

    public static function getSubscribedEvents()
    {
        return [
            AssetEvents::POST_UPDATE => 'onSaveAfter'
        ];
    }
}
