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

/**
 * This defines the interface for a persistent targeting storage (e.g. Session). The targeting storage needs to define
 * by itself if it needs a unique visitor ID to store data and fetch if from the visitor info itself.
 */
interface TargetingStorageInterface
{
    public function has(VisitorInfo $visitorInfo, string $name): bool;

    public function set(VisitorInfo $visitorInfo, string $name, $value);

    public function get(VisitorInfo $visitorInfo, string $name, $default = null);
}
