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
use Pimcore\Targeting\Session\SessionConfigurator;
use Pimcore\Targeting\Storage\Traits\TimestampsTrait;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

class SessionStorage implements TargetingStorageInterface
{
    use TimestampsTrait;

    const STORAGE_KEY_CREATED_AT = '_c';
    const STORAGE_KEY_UPDATED_AT = '_u';

    public function all(VisitorInfo $visitorInfo, string $scope): array
    {
        $bag = $this->getSessionBag($visitorInfo, $scope, true);
        if (null === $bag) {
            return [];
        }

        $blacklist = [
            self::STORAGE_KEY_CREATED_AT,
            self::STORAGE_KEY_UPDATED_AT,
            self::STORAGE_KEY_META_ENTRY,
        ];

        // filter internal values
        $result = array_filter($bag->all(), function ($key) use ($blacklist) {
            return !in_array($key, $blacklist, true);
        }, ARRAY_FILTER_USE_KEY);

        return $result;
    }

    public function has(VisitorInfo $visitorInfo, string $scope, string $name): bool
    {
        $bag = $this->getSessionBag($visitorInfo, $scope, true);
        if (null === $bag) {
            return false;
        }

        return $bag->has($name);
    }

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, $value)
    {
        $bag = $this->getSessionBag($visitorInfo, $scope);
        if (null === $bag) {
            return;
        }

        $bag->set($name, $value);

        $this->updateTimestamps($bag);
    }

    public function get(VisitorInfo $visitorInfo, string $scope, string $name, $default = null)
    {
        $bag = $this->getSessionBag($visitorInfo, $scope, true);
        if (null === $bag) {
            return $default;
        }

        return $bag->get($name, $default);
    }

    public function clear(VisitorInfo $visitorInfo, string $scope = null)
    {
        if (null !== $scope) {
            $bag = $this->getSessionBag($visitorInfo, $scope, true);
            if (null !== $bag) {
                $bag->clear();
            }
        } else {
            foreach (self::VALID_SCOPES as $sc) {
                $bag = $this->getSessionBag($visitorInfo, $sc, true);
                if (null !== $bag) {
                    $bag->clear();
                }
            }
        }
    }

    public function migrateFromStorage(TargetingStorageInterface $storage, VisitorInfo $visitorInfo, string $scope)
    {
        // only allow migration if a session bag is available as otherwise the fallback
        // would clear the original storage although data was not stored
        $bag = $this->getSessionBag($visitorInfo, $scope);
        if (null === $bag) {
            throw new \LogicException('Can\'t migrate to Session storage as session bag could not be loaded');
        }

        $values = $storage->all($visitorInfo, $scope);
        foreach ($values as $name => $value) {
            $bag->set($name, $value);
        }

        // update created/updated at from storage
        $this->updateTimestamps(
            $bag,
            $storage->getCreatedAt($visitorInfo, $scope),
            $storage->getUpdatedAt($visitorInfo, $scope)
        );
    }

    public function getCreatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        $bag = $this->getSessionBag($visitorInfo, $scope);
        if (null === $bag || !$bag->has(self::STORAGE_KEY_CREATED_AT)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', (string)$bag->get(self::STORAGE_KEY_CREATED_AT));
    }

    public function getUpdatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        $bag = $this->getSessionBag($visitorInfo, $scope);

        if (null === $bag || !$bag->has(self::STORAGE_KEY_UPDATED_AT)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', (string)$bag->get(self::STORAGE_KEY_UPDATED_AT));
    }

    /**
     * Loads a session bag
     *
     * @param VisitorInfo $visitorInfo
     * @param string $scope
     * @param bool $checkPreviousSession
     *
     * @return null|NamespacedAttributeBag
     */
    private function getSessionBag(VisitorInfo $visitorInfo, string $scope, bool $checkPreviousSession = false)
    {
        $request = $visitorInfo->getRequest();

        if (!$request->hasSession()) {
            return null;
        }

        if ($checkPreviousSession && !$request->hasPreviousSession()) {
            return null;
        }

        $session = $request->getSession();

        /** @var NamespacedAttributeBag $bag */
        $bag = null;

        switch ($scope) {
            case self::SCOPE_SESSION:
                $bag = $session->getBag(SessionConfigurator::TARGETING_BAG_SESSION);
                break;

            case self::SCOPE_VISITOR:
                $bag = $session->getBag(SessionConfigurator::TARGETING_BAG_VISITOR);
                break;

            default:
                throw new \InvalidArgumentException(sprintf(
                    'The session storage is not able to handle the "%s" scope',
                    $scope
                ));
        }

        return $bag;
    }

    private function updateTimestamps(
        NamespacedAttributeBag $bag,
        \DateTimeInterface $createdAt = null,
        \DateTimeInterface $updatedAt = null
    ) {
        $timestamps = $this->normalizeTimestamps($createdAt, $updatedAt);

        if (!$bag->has(self::STORAGE_KEY_CREATED_AT)) {
            $bag->set(self::STORAGE_KEY_CREATED_AT, $timestamps['createdAt']->getTimestamp());
            $bag->set(self::STORAGE_KEY_UPDATED_AT, $timestamps['updatedAt']->getTimestamp());
        } else {
            $bag->set(self::STORAGE_KEY_UPDATED_AT, $timestamps['updatedAt']->getTimestamp());
        }
    }
}
