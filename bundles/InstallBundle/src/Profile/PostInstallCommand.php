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

namespace Pimcore\Bundle\InstallBundle\Profile;

/**
 * Represents a Symfony console command to run after installation.
 */
final readonly class PostInstallCommand
{
    /**
     * @param string       $command   Symfony console command name (e.g. 'cache:clear')
     * @param string       $label     Human-readable description for CLI output
     * @param int          $priority  Execution order: higher values run first (descending sort)
     * @param list<string> $arguments Additional arguments to pass to the command
     */
    public function __construct(
        private string $command,
        private string $label,
        private int $priority = 0,
        private array $arguments = [],
    ) {
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return list<string>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
