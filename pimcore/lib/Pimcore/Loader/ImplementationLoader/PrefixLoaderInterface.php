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

namespace Pimcore\Loader\ImplementationLoader;

/**
 * Iterates an array of namespace prefixes and tries to load classes by namespace/prefix.
 */
interface PrefixLoaderInterface extends LoaderInterface
{
    /**
     * Adds a prefix to the list of searched prefixes. The normalizer will be used to build the class name.
     *
     * @param string $prefix
     * @param callable $normalizer
     */
    public function addPrefix(string $prefix, callable $normalizer = null);
}
