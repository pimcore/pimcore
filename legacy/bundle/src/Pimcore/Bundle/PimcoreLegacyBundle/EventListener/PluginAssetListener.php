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

namespace Pimcore\Bundle\PimcoreLegacyBundle\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Pimcore\ExtensionManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginAssetListener implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            BundleManagerEvents::JS_PATHS           => 'onPathsEvent',
            BundleManagerEvents::CSS_PATHS          => 'onPathsEvent',
            BundleManagerEvents::EDITMODE_JS_PATHS  => 'onPathsEvent',
            BundleManagerEvents::EDITMODE_CSS_PATHS => 'onPathsEvent'
        ];
    }

    public function onPathsEvent(PathsEvent $event, $eventName)
    {
        $type     = null;
        $editmode = false;

        switch($eventName) {
            case BundleManagerEvents::JS_PATHS:
                $type = 'js';
                break;

            case BundleManagerEvents::CSS_PATHS:
                $type = 'css';
                break;

            case BundleManagerEvents::EDITMODE_JS_PATHS:
                $type     = 'js';
                $editmode = true;
                break;

            case BundleManagerEvents::EDITMODE_CSS_PATHS:
                $type     = 'css';
                $editmode = true;
                break;
        }

        $paths = ExtensionManager::getAssetPaths($type, $editmode);
        if (count($paths) > 0) {
            $event->addPaths($paths);
        }
    }
}
