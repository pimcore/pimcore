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
use Pimcore\Targeting\Storage\Traits\TimestampsTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Stores data as cookie in the client's browser
 *
 * NOTE: using this storage without signed cookies is inherently insecure and can open vulnerabilities by injecting
 * malicious data into the client cookie. Use only for testing!
 */
class CookieStorage implements TargetingStorageInterface
{
    use TimestampsTrait;

    const COOKIE_NAME_SESSION = '_pc_tss'; // tss = targeting session storage
    const COOKIE_NAME_VISITOR = '_pc_tvs'; // tvs = targeting visitor storage

    const STORAGE_KEY_CREATED_AT = '_c';
    const STORAGE_KEY_UPDATED_AT = '_u';

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
    private $data = [];

    /**
     * @var bool
     */
    private $changed = false;

    /**
     * @var array
     */
    private $scopeCookieMapping = [
        self::SCOPE_SESSION => self::COOKIE_NAME_SESSION,
        self::SCOPE_VISITOR => self::COOKIE_NAME_VISITOR,
    ];

    public function __construct(
        CookieSaveHandlerInterface $saveHandler,
        RequestStack $requestHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->saveHandler = $saveHandler;
        $this->requestStack = $requestHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function all(VisitorInfo $visitorInfo, string $scope): array
    {
        $this->loadData($visitorInfo, $scope);

        $blacklist = [
            self::STORAGE_KEY_CREATED_AT,
            self::STORAGE_KEY_UPDATED_AT,
            self::STORAGE_KEY_META_ENTRY,
        ];

        // filter internal values
        $result = array_filter($this->data[$scope], function ($key) use ($blacklist) {
            return !in_array($key, $blacklist, true);
        }, ARRAY_FILTER_USE_KEY);

        return $result;
    }

    public function has(VisitorInfo $visitorInfo, string $scope, string $name): bool
    {
        $this->loadData($visitorInfo, $scope);

        return isset($this->data[$scope][$name]);
    }

    public function get(VisitorInfo $visitorInfo, string $scope, string $name, $default = null)
    {
        $this->loadData($visitorInfo, $scope);

        if (isset($this->data[$scope][$name])) {
            return $this->data[$scope][$name];
        }

        return $default;
    }

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, $value)
    {
        $this->loadData($visitorInfo, $scope);

        $this->data[$scope][$name] = $value;

        $this->updateTimestamps($scope);
        $this->addSaveListener($visitorInfo);
    }

    public function clear(VisitorInfo $visitorInfo, string $scope = null)
    {
        if (null === $scope) {
            $this->data = [];
        } else {
            if (isset($this->data[$scope])) {
                unset($this->data[$scope]);
            }
        }

        $this->addSaveListener($visitorInfo);
    }

    public function migrateFromStorage(TargetingStorageInterface $storage, VisitorInfo $visitorInfo, string $scope)
    {
        $values = $storage->all($visitorInfo, $scope);

        $this->loadData($visitorInfo, $scope);

        foreach ($values as $name => $value) {
            $this->data[$scope][$name] = $value;
        }

        // update created/updated at from storage
        $this->updateTimestamps(
            $scope,
            $storage->getCreatedAt($visitorInfo, $scope),
            $storage->getUpdatedAt($visitorInfo, $scope)
        );

        $this->addSaveListener($visitorInfo);
    }

    public function getCreatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        $this->loadData($visitorInfo, $scope);

        if (!isset($this->data[$scope][self::STORAGE_KEY_CREATED_AT])) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', (string)$this->data[$scope][self::STORAGE_KEY_CREATED_AT]);
    }

    public function getUpdatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        $this->loadData($visitorInfo, $scope);

        if (!isset($this->data[$scope][self::STORAGE_KEY_UPDATED_AT])) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', (string)$this->data[$scope][self::STORAGE_KEY_CREATED_AT]);
    }

    private function loadData(VisitorInfo $visitorInfo, string $scope): array
    {
        if (!isset($this->scopeCookieMapping[$scope])) {
            throw new \InvalidArgumentException(sprintf('Scope "%s" is not supported', $scope));
        }

        if (isset($this->data[$scope]) && null !== $this->data[$scope]) {
            return $this->data[$scope];
        }

        $request = $visitorInfo->getRequest();

        $this->data[$scope] = $this->saveHandler->load($request, $scope, $this->scopeCookieMapping[$scope]);

        return $this->data[$scope];
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

    private function updateTimestamps(
        string $scope,
        \DateTimeInterface $createdAt = null,
        \DateTimeInterface $updatedAt = null
    ) {
        $timestamps = $this->normalizeTimestamps($createdAt, $updatedAt);

        if (!isset($this->data[$scope][self::STORAGE_KEY_CREATED_AT])) {
            $this->data[$scope][self::STORAGE_KEY_CREATED_AT] = $timestamps['createdAt']->getTimestamp();
            $this->data[$scope][self::STORAGE_KEY_UPDATED_AT] = $timestamps['updatedAt']->getTimestamp();
        } else {
            $this->data[$scope][self::STORAGE_KEY_UPDATED_AT] = $timestamps['updatedAt']->getTimestamp();
        }
    }

    protected function expiryFor(string $scope)
    {
        $expiry = 0;
        if (self::SCOPE_VISITOR === $scope) {
            $expiry = new \DateTime('+1 year');
        } elseif (self::SCOPE_SESSION === $scope) {
            $expiry = new \DateTime('+30 minutes');
        }

        return $expiry;
    }
}
