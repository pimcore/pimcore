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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Http\Request\Resolver\OutputTimestampResolver;
use Pimcore\Tool\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OutputTimestampListener implements EventSubscriberInterface
{
    const TIMESTAMP_OVERRIDE_PARAM_NAME = 'pimcore_override_output_timestamp';

    /**
     * @var OutputTimestampResolver
     */
    protected $outputTimestampResolver;

    public function __construct(OutputTimestampResolver $outputTimestampResolver)
    {
        $this->outputTimestampResolver = $outputTimestampResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($overrideTimestamp = (int)$event->getRequest()->query->get(self::TIMESTAMP_OVERRIDE_PARAM_NAME)) {
            if (\Pimcore::inDebugMode() || Authentication::authenticateSession($event->getRequest())) {
                $this->outputTimestampResolver->setOutputTimestamp($overrideTimestamp);
            }
        }
    }
}
