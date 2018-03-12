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

namespace Pimcore\FeatureToggles;

interface FeatureContextInterface extends \Countable, \IteratorAggregate
{
    public function all(): array;

    public function keys(): array;

    public function get(string $key, $default = null);

    public function set(string $key, $value);

    public function has(string $key): bool;

    public function remove(string $key);
}
