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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingCodeAwareInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class TrackingCodeFlashMessageListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    const FLASH_MESSAGE_BAG_KEY = 'ecommerceframework_trackingcode_flashmessagelistener';

    /**
     * @var RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * @var TrackingManager
     */
    protected $trackingManger;

    public function __construct(RequestStack $requestStack, TrackingManager $trackingManager)
    {
        $this->requestStack = $requestStack;
        $this->trackingManger = $trackingManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        // Check FlashBag cookie exists to avoid autostart session by accessing the FlashBag.
        $flashBagCookie = (bool)$request->cookies->get(self::FLASH_MESSAGE_BAG_KEY);
        $session = $this->requestStack->getSession();
        if ($flashBagCookie && $session instanceof Session) {
            $trackedCodes = $session->getFlashBag()->get(self::FLASH_MESSAGE_BAG_KEY);

            if (is_array($trackedCodes) && count($trackedCodes)) {
                foreach ($this->trackingManger->getTrackers() as $tracker) {
                    if ($tracker instanceof TrackingCodeAwareInterface && isset($trackedCodes[get_class($tracker)])) {
                        foreach ($trackedCodes[get_class($tracker)] as $trackedCode) {
                            $tracker->trackCode($trackedCode);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $session = $this->requestStack->getSession();

        /**
         * If tracking codes are forwarded as FlashMessage, then set a cookie which is checked in subsequent request for successful handshake
         * else clear cookie, if set and FlashBag is already processed.
         */
        if (
            $session instanceof Session &&
            $session->isStarted() &&
            $session->getFlashBag()->has(self::FLASH_MESSAGE_BAG_KEY)
        ) {
            $response->headers->setCookie(new Cookie(self::FLASH_MESSAGE_BAG_KEY, '1'));
            $response->headers->set('X-Pimcore-Output-Cache-Disable-Reason', 'Tracking Codes Passed');
        } elseif ($request->cookies->has(self::FLASH_MESSAGE_BAG_KEY)) {
            $response->headers->clearCookie(self::FLASH_MESSAGE_BAG_KEY);
        }
    }
}
