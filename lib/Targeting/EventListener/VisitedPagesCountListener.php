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

use Pimcore\Event\Targeting\TargetingEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Targeting\Service\VisitedPagesCounter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VisitedPagesCountListener implements EventSubscriberInterface
{
    /**
     * @var VisitedPagesCounter
     */
    private $visitedPagesCounter;

    /**
     * @var bool
     */
    private $recordPageCount = false;

    public function __construct(VisitedPagesCounter $visitedPagesCounter)
    {
        $this->visitedPagesCounter = $visitedPagesCounter;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            TargetingEvents::VISITED_PAGES_COUNT_MATCH => 'onVisitedPagesCountMatch', // triggered from conditions depending on page count
            TargetingEvents::POST_RESOLVE => 'onPostResolveVisitorInfo',
        ];
    }

    public function onVisitedPagesCountMatch()
    {
        // increment page count after matching proceeded
        $this->recordPageCount = true;
    }

    public function onPostResolveVisitorInfo(TargetingEvent $event)
    {
        // TODO currently the pages count is only recorded if there's a condition depending on
        // the count. This is good for minimizing storage data and writes, but implies that the
        // page count is not recorded if there's no rule with a condition depending on the page
        // count. Alternatively this could be done blindly after resolving the visitor info, but
        // that would trigger a write/increment on every request without actually needing the data.
        if (!$this->recordPageCount) {
            return;
        }

        $this->visitedPagesCounter->increment($event->getVisitorInfo());
    }
}
