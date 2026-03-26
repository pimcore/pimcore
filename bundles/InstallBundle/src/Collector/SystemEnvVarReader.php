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
 * Reads environment variables from the system ($_ENV, $_SERVER, getenv()).
 *
 * This is the production implementation of EnvVarReaderInterface.
 *
 * @internal
 */
final readonly class SystemEnvVarReader implements EnvVarReaderInterface
{
    public function get(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if ($value === false || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
