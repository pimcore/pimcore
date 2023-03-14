<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Storage;

use Pimcore\Bundle\PersonalizationBundle\Targeting\EventListener\TargetingSessionBagListener;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\Traits\TimestampsTrait;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

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

        $blocklist = [
            self::STORAGE_KEY_CREATED_AT,
            self::STORAGE_KEY_UPDATED_AT,
            self::STORAGE_KEY_META_ENTRY,
        ];

        // filter internal values
        $result = array_filter($bag->all(), function ($key) use ($blocklist) {
            return !in_array($key, $blocklist, true);
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

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, mixed $value): void
    {
        $bag = $this->getSessionBag($visitorInfo, $scope);
        if (null === $bag) {
            return;
        }

        $bag->set($name, $value);

        $this->updateTimestamps($bag);
    }

    /**
     * {@inheritdoc }
     */
    public function get(VisitorInfo $visitorInfo, string $scope, string $name, mixed $default = null): mixed
    {
        $bag = $this->getSessionBag($visitorInfo, $scope, true);
        if (null === $bag) {
            return $default;
        }

        return $bag->get($name, $default);
    }

    /**
     * {@inheritdoc }
     */
    public function clear(VisitorInfo $visitorInfo, string $scope = null): void
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

    public function migrateFromStorage(TargetingStorageInterface $storage, VisitorInfo $visitorInfo, string $scope): void
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

    public function getCreatedAt(VisitorInfo $visitorInfo, string $scope): ?\DateTimeImmutable
    {
        $bag = $this->getSessionBag($visitorInfo, $scope);
        if (null === $bag || !$bag->has(self::STORAGE_KEY_CREATED_AT)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', (string)$bag->get(self::STORAGE_KEY_CREATED_AT));
    }

    public function getUpdatedAt(VisitorInfo $visitorInfo, string $scope): ?\DateTimeImmutable
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
     * @throws \Exception
     */
    private function getSessionBag(VisitorInfo $visitorInfo, string $scope, bool $checkPreviousSession = false): ?AttributeBag
    {
        $request = $visitorInfo->getRequest();

        if (!$request->hasSession()) {
            return null;
        }

        if ($checkPreviousSession && !$request->hasPreviousSession()) {
            return null;
        }

        $session = $request->getSession();

        switch ($scope) {
            case self::SCOPE_SESSION:
                $bag = $session->getBag(TargetingSessionBagListener::TARGETING_BAG_SESSION);

                break;

            case self::SCOPE_VISITOR:
                $bag = $session->getBag(TargetingSessionBagListener::TARGETING_BAG_VISITOR);

                break;

            default:
                throw new \InvalidArgumentException(sprintf(
                    'The session storage is not able to handle the "%s" scope',
                    $scope
                ));
        }

        if ($bag instanceof AttributeBag) {
            return $bag;
        }

        throw new \Exception('wrong type');
    }

    private function updateTimestamps(
        AttributeBag $bag,
        \DateTimeInterface $createdAt = null,
        \DateTimeInterface $updatedAt = null
    ): void {
        $timestamps = $this->normalizeTimestamps($createdAt, $updatedAt);

        if (!$bag->has(self::STORAGE_KEY_CREATED_AT)) {
            $bag->set(self::STORAGE_KEY_CREATED_AT, $timestamps['createdAt']->getTimestamp());
            $bag->set(self::STORAGE_KEY_UPDATED_AT, $timestamps['updatedAt']->getTimestamp());
        } else {
            $bag->set(self::STORAGE_KEY_UPDATED_AT, $timestamps['updatedAt']->getTimestamp());
        }
    }
}
