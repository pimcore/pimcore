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
use Pimcore\Targeting\Storage\Cookie\CookieSaveHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieStorage implements TargetingStorageInterface
{
    /**
     * @var CookieSaveHandlerInterface
     */
    private $saveHandler;

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
        CookieSaveHandlerInterface $saveHandler,
        RequestStack $requestHelper,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->saveHandler     = $saveHandler;
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

    private function validateScope(string $scope)
    {
        if (!isset($this->scopeCookieMapping[$scope])) {
            throw new \InvalidArgumentException(sprintf('Scope "%s" is not supported', $scope));
        }
    }

    private function loadData(VisitorInfo $visitorInfo): array
    {
        if (null !== $this->data) {
            return $this->data;
        }

        $request = $visitorInfo->getRequest();

        $data = [];
        foreach (array_keys($this->scopeCookieMapping) as $scope) {
            $data[$scope] = $this->saveHandler->load($request, $scope, $this->scopeCookieMapping[$scope]);
        }

        $this->data = $data;

        return $this->data;
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
                $this->saveHandler->save(
                    $response,
                    $scope,
                    $this->scopeCookieMapping[$scope],
                    $this->expiryFor($scope),
                    $this->data[$scope] ?? null
                );
            }
        };

        $this->eventDispatcher->addListener(KernelEvents::RESPONSE, $listener);
    }

    protected function expiryFor(string $scope)
    {
        $expire = 0;
        if (self::SCOPE_VISITOR === $scope) {
            $expire = new \DateTime('+1 year');
        }

        return $expire;
    }
}
