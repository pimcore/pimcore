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
 * In-memory implementation of EnvVarReaderInterface for testing.
 *
 * Allows tests to inject predefined env var values without
 * polluting the process environment ($_ENV, $_SERVER, getenv()).
 *
 * @internal
 */
final class ArrayEnvVarReader implements EnvVarReaderInterface
{
    /** @var array<string, string> */
    private array $vars;

    /**
     * @param array<string, string> $vars env var name => value
     */
    public function __construct(array $vars = [])
    {
        $this->vars = $vars;
    }

    public function get(string $name): ?string
    {
        $value = $this->vars[$name] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Set an env var value at runtime (useful in test setup).
     */
    public function set(string $name, string $value): void
    {
        $this->vars[$name] = $value;
    }
}
