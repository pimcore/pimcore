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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieStorage implements TargetingStorageInterface
{
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

    /**
     * @var array
     */
    private $scopeCookieMapping = [
        self::SCOPE_SESSION => '_pc_tss', // tss = targeting session storage
        self::SCOPE_VISITOR => '_pc_tvs', // tvs = targeting visitor storage
    ];

    public function __construct(
        RequestStack $requestHelper,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->requestStack    = $requestHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function has(VisitorInfo $visitorInfo, string $scope, string $name): bool
    {
        $this->validateScope($scope);
        $this->loadData($visitorInfo);

        return isset($this->data[$scope][$name]);
    }

    public function get(VisitorInfo $visitorInfo, string $scope, string $name, $default = null)
    {
        $this->validateScope($scope);
        $this->loadData($visitorInfo);

        if (isset($this->data[$scope][$name])) {
            return $this->data[$scope][$name];
        }

        return $default;
    }

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, $value)
    {
        $this->validateScope($scope);
        $this->loadData($visitorInfo);

        $this->data[$scope][$name] = $value;

        $this->addSaveListener($visitorInfo);
    }

    public function all(VisitorInfo $visitorInfo, string $scope): array
    {
        $this->validateScope($scope);
        $this->loadData($visitorInfo);

        return $this->data[$scope];
    }

    public function clear(VisitorInfo $visitorInfo, string $scope = null)
    {
        if (null === $scope) {
            $this->data = [];
        } else {
            $this->validateScope($scope);
            $this->loadData($visitorInfo);

            $this->data[$scope] = [];
        }

        $this->addSaveListener($visitorInfo);
    }

    private function loadData(VisitorInfo $visitorInfo): array
    {
        if (null !== $this->data) {
            return $this->data;
        }

        $request = $visitorInfo->getRequest();

        $data = [
            self::SCOPE_VISITOR => $this->loadScopeData($request, self::SCOPE_VISITOR),
            self::SCOPE_SESSION => $this->loadScopeData($request, self::SCOPE_SESSION),
        ];

        $this->data = $data;

        return $data;
    }

    private function loadScopeData(Request $request, string $scope): array
    {
        $this->validateScope($scope);

        $cookie = $request->cookies->get($this->scopeCookieMapping[$scope], null);

        if (null === $cookie) {
            return [];
        }

        $json = json_decode($cookie, true);
        if (is_array($json)) {
            return $json;
        }

        return [];
    }

    private function validateScope(string $scope)
    {
        if (!isset($this->scopeCookieMapping[$scope])) {
            throw new \InvalidArgumentException(sprintf('Scope "%s" is not supported', $scope));
        }
    }

    private function addSaveListener(VisitorInfo $visitorInfo)
    {
        if ($this->changed) {
            return;
        }

        $this->changed = true;

        // adds a response listener setting the storage cookie
        $listener = function (FilterResponseEvent $event) use ($visitorInfo) {
            // only handle event for the visitor info which triggered the save
            if ($event->getRequest() !== $visitorInfo->getRequest()) {
                return;
            }

            $response = $event->getResponse();

            foreach (array_keys($this->scopeCookieMapping) as $scope) {
                $data = $this->data[$scope] ?? [];

                if (empty($data)) {
                    $this->setScopeCookie($response, $scope, null);
                } else {
                    $this->setScopeCookie($response, $scope, json_encode($data));
                }
            }
        };

        $this->eventDispatcher->addListener(KernelEvents::RESPONSE, $listener);
    }

    protected function setScopeCookie(Response $response, string $scope, $value)
    {
        $cookieName = $this->scopeCookieMapping[$scope];

        $expire = 0;
        if (self::SCOPE_VISITOR === $scope) {
            $expire = new \DateTime('+1 year');
        }

        $response->headers->setCookie(new Cookie(
            $cookieName,
            $value,
            $expire,
            '/',
            null,
            false,
            false
        ));
    }
}
