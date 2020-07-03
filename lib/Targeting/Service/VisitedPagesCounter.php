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

namespace Pimcore\Targeting\Service;

use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Storage\TargetingStorageInterface;

/**
 * Makes sure a page visit is counted only once per request.
 */
class VisitedPagesCounter
{
    const STORAGE_KEY = 'pgc';

    /**
     * @var TargetingStorageInterface
     */
    private $targetingStorage;

    /**
     * @var bool
     */
    private $incremented = false;

    public function __construct(TargetingStorageInterface $targetingStorage)
    {
        $this->targetingStorage = $targetingStorage;
    }

    public function getCount(VisitorInfo $visitorInfo, string $scope = TargetingStorageInterface::SCOPE_VISITOR): int
    {
        return $this->targetingStorage->get($visitorInfo, $scope, self::STORAGE_KEY, 0);
    }

    public function increment(VisitorInfo $visitorInfo, string $scope = TargetingStorageInterface::SCOPE_VISITOR, bool $force = false)
    {
        if ($this->incremented && !$force) {
            return;
        }

        // TODO to make sure this works in concurrent request we probably need
        // to support some kind of transactional updates on the storage
        $count = $this->getCount($visitorInfo, $scope);
        $count++;

        $this->targetingStorage->set($visitorInfo, $scope, self::STORAGE_KEY, $count);

        $this->incremented = true;
    }
}
