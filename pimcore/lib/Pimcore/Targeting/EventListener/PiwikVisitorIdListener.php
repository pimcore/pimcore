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

namespace Pimcore\Targeting\EventListener;

use Pimcore\Analytics\Piwik\Event\TrackingDataEvent;
use Pimcore\Analytics\Piwik\Tracker;
use Pimcore\Event\Analytics\PiwikEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PiwikVisitorIdListener implements EventSubscriberInterface
{
    /**
     * @var TargetingListener
     */
    private $targetingListener;

    public function __construct(TargetingListener $targetingListener)
    {
        $this->targetingListener = $targetingListener;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PiwikEvents::CODE_TRACKING_DATA => 'onPiwikTrackingData',
        ];
    }

    public function onPiwikTrackingData(TrackingDataEvent $event)
    {
        if (!$this->targetingListener->isEnabled()) {
            return;
        }

        $snippet = <<<'EOF'
_paq.push([function() { 'undefined' !== typeof window._ptg && _ptg.api.setVisitorId(this.getVisitorId()); }]);
EOF;

        // sets visitor ID to piwik's user ID
        $event->getBlock(Tracker::BLOCK_AFTER_TRACK)->append($snippet);
    }
}
