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

namespace Pimcore\HttpKernel\BundleCollection;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface ItemInterface
{
    const SOURCE_PROGRAMATICALLY          = 'programatically';
    const SOURCE_EXTENSION_MANAGER_CONFIG = 'extension_manager_config';

    public function getBundleIdentifier(): string;

    public function getBundle(): BundleInterface;

    public function isPimcoreBundle(): bool;

    public function getPriority(): int;

    public function getEnvironments(): array;

    public function matchesEnvironment(string $environment): bool;

    public function getSource(): string;
}
