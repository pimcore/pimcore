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

namespace Pimcore\Composer\Config;

/**
 * Normalizes the composer.json config after merging it from an update. This can include
 * removing invalid configs or package names as the merge process is only additive.
 */
interface NormalizerInterface
{
    public function normalize(array $config): array;
}
