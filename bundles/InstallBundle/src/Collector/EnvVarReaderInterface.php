<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\Collector;

/**
 * Abstraction for reading environment variables.
 *
 * Implementations provide the actual lookup strategy (system env, in-memory array, etc.).
 * This allows the ParameterCollector to be tested without polluting the process environment.
 *
 * @internal
 */
interface EnvVarReaderInterface
{
    /**
     * Read an environment variable by name.
     *
     * Returns null if the variable is not set or is empty.
     */
    public function get(string $name): ?string;
}
