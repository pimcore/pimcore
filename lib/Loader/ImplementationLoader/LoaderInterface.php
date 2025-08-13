<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Loader\ImplementationLoader;

/**
 * @internal
 */
interface LoaderInterface
{
    /**
     * Checks if implementation is supported
     *
     *
     */
    public function supports(string $name): bool;

    /**
     * Builds an implementation instance
     *
     *
     */
    public function build(string $name, array $params = []): mixed;
}
