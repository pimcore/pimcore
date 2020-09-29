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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingCodeAwareInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TrackingCodeFlashMessageListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    const FLASH_MESSAGE_BAG_KEY = 'ecommerceframework_trackingcode_flashmessagelistener';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var TrackingManager
     */
    protected $trackingManger;

    public function __construct(SessionInterface $session, TrackingManager $trackingManager)
    {
        $this->session = $session;
        $this->trackingManger = $trackingManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        // Check FlashBag cookie exists to avoid autostart session by accessing the FlashBag.
        $flashBagCookie = (bool)$request->cookies->get(self::FLASH_MESSAGE_BAG_KEY, false);
        if ($flashBagCookie) {
            $trackedCodes = $this->session->getFlashBag()->get(self::FLASH_MESSAGE_BAG_KEY);

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
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        /**
         * If tracking codes are forwarded as FlashMessage, then set a cookie which is checked in subsequent request for successful handshake
         * else clear cookie, if set and FlashBag is already processed.
         */
        if ($this->session->isStarted() && $this->session->getFlashBag()->has(self::FLASH_MESSAGE_BAG_KEY)) {
            $response->headers->setCookie(new Cookie(self::FLASH_MESSAGE_BAG_KEY, true));
            $response->headers->set('X-Pimcore-Output-Cache-Disable-Reason', 'Tracking Codes Passed', true);
        } elseif ($request->cookies->has(self::FLASH_MESSAGE_BAG_KEY)) {
            $response->headers->clearCookie(self::FLASH_MESSAGE_BAG_KEY);
        }
    }
}
