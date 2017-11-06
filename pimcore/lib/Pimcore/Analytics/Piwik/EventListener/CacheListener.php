<?php

declare(strict_types=1);

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

namespace Pimcore\Analytics\Piwik\EventListener;

use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Event\Admin\Report\SettingsEvent;
use Pimcore\Event\Admin\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheListener implements EventSubscriberInterface
{
    /**
     * @var CoreHandlerInterface
     */
    private $cache;

    /**
     * @param CoreHandlerInterface $cache
     */
    public function __construct(CoreHandlerInterface $cache)
    {
        $this->cache = $cache;
    }

    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::SAVE_SETTINGS => 'onSaveSettings'
        ];
    }

    public function onSaveSettings(SettingsEvent $event)
    {
        // clear piwik cache tag when report settings are saved
        // to make sure cached data (e.g. widgets) is refreshed
        $this->cache->clearTag('piwik');
    }
}
