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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider\TargetingStorage;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProviderDependentInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\TargetingStorageInterface;

class TimeOnSite implements ConditionInterface, DataProviderDependentInterface
{
    private int $seconds;

    public function __construct(int $seconds)
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('Seconds needs to be a positive integer');
        }

        $this->seconds = $seconds;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(array $config): static
    {
        $seconds = $config['seconds'] ?? 0;
        $seconds += ($config['minutes'] ?? 0) * 60;
        $seconds += ($config['hours'] ?? 0) * 60 * 60;

        return new static($seconds);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProviderKeys(): array
    {
        return [TargetingStorage::PROVIDER_KEY];
    }

    /**
     * {@inheritdoc}
     */
    public function canMatch(): bool
    {
        return $this->seconds > 0;
    }

    /**
     * {@inheritdoc}
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
