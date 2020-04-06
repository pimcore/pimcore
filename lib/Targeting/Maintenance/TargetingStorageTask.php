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

namespace Pimcore\Targeting\Maintenance;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Targeting\Storage\MaintenanceStorageInterface;
use Pimcore\Targeting\Storage\TargetingStorageInterface;

class TargetingStorageTask implements TaskInterface
{
    /**
     * @var TargetingStorageInterface
     */
    private $targetingStorage;

    /**
     * @param TargetingStorageInterface $targetingStorage
     */
    public function __construct(TargetingStorageInterface $targetingStorage)
    {
        $this->targetingStorage = $targetingStorage;
    }

    public function execute()
    {
        if (!$this->targetingStorage instanceof MaintenanceStorageInterface) {
            return;
        }

        $this->targetingStorage->maintenance();
    }
}
