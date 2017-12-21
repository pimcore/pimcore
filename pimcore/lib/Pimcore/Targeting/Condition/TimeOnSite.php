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

namespace Pimcore\Targeting\Condition;

use Pimcore\Targeting\DataProvider\TargetingStorage;
use Pimcore\Targeting\DataProviderDependentInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Storage\TargetingStorageInterface;

class TimeOnSite implements ConditionInterface, DataProviderDependentInterface
{
    /**
     * @var int
     */
    private $seconds;

    public function __construct(int $seconds)
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('Seconds needs to be a positive integer');
        }

        $this->seconds = $seconds;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        $seconds = $config['seconds'] ?? 0;
        $seconds += ($config['minutes'] ?? 0) * 60;
        $seconds += ($config['hours'] ?? 0) * 60 * 60;

        return new static($seconds);
    }

    /**
     * @inheritDoc
     */
    public function getDataProviderKeys(): array
    {
        return [TargetingStorage::PROVIDER_KEY];
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return $this->seconds > 0;
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        /** @var TargetingStorageInterface $storage */
        $storage = $visitorInfo->get(TargetingStorage::PROVIDER_KEY);

        // set/update meta value to make sure storage updates/creates its timestamps
        $storage->set(
            $visitorInfo,
            TargetingStorageInterface::SCOPE_SESSION,
            TargetingStorageInterface::STORAGE_KEY_META_ENTRY,
            1
        );

        $createdAt = $storage->getCreatedAt($visitorInfo, TargetingStorageInterface::SCOPE_SESSION);
        if (null === $createdAt) {
            return false;
        }

        $timeOnSite = time() - $createdAt->getTimestamp();

        return $timeOnSite >= $this->seconds;
    }
}
