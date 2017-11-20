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

namespace Pimcore\Targeting\Storage;

use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieStorage implements TargetingStorageInterface
{
    const COOKIE_NAME = '_pc_str';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $data;

    /**
     * @var bool
     */
    private $changed = false;

    public function __construct(
        RequestStack $requestHelper,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->requestStack    = $requestHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function has(VisitorInfo $visitorInfo, string $name): bool
    {
        if (null === $this->data) {
            $this->data = $this->loadData($visitorInfo);
        }

        return isset($this->data[$name]);
    }

    public function set(VisitorInfo $visitorInfo, string $name, $value)
    {
        if (null === $this->data) {
            $this->data = $this->loadData($visitorInfo);
        }

        $this->data[$name] = $value;

        if (!$this->changed) {
            $this->changed = true;
            $this->addSaveListener($visitorInfo);
        }
    }

    public function get(VisitorInfo $visitorInfo, string $name, $default = null)
    {
        if (null === $this->data) {
            $this->data = $this->loadData($visitorInfo);
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return $default;
    }

    private function loadData(VisitorInfo $visitorInfo): array
    {
        if (null !== $this->data) {
            return $this->data;
        }

        $request = $visitorInfo->getRequest();
        $cookie  = $request->cookies->get(self::COOKIE_NAME, null);

        if (null === $cookie) {
            return [];
        }

        $json = json_decode($cookie, true);

        if (is_array($json)) {
            return $json;
        }

        return [];
    }

    private function addSaveListener(VisitorInfo $visitorInfo)
    {
        // adds a response listener setting the storage cookie
        $listener = function (FilterResponseEvent $event) use ($visitorInfo) {
            // only handle event for the visitor info which triggered the save
            if ($event->getRequest() !== $visitorInfo->getRequest()) {
                return;
            }

            $json = json_encode($this->data);

            $response = $event->getResponse();
            $response->headers->setCookie($this->createSaveCookie($json));
        };

        $this->eventDispatcher->addListener(KernelEvents::RESPONSE, $listener);
    }

    protected function createSaveCookie(string $data): Cookie
    {
        return new Cookie(
            self::COOKIE_NAME,
            $data,
            (new \DateTime('+7 days')),
            '/',
            null,
            false,
            false
        );
    }
}
