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
 *
 * @internal
 */
final readonly class PostInstallCommand
{
    /**
     * @param string $command   Symfony console command name (e.g. 'cache:clear')
     * @param string $label     Human-readable description for CLI output
     * @param int    $priority  Execution order: higher values run first (descending sort)
     * @param bool   $idempotent Whether re-running this command is safe on partial installs
     */
    public function __construct(
        private string $command,
        private string $label,
        private int $priority = 0,
        private bool $idempotent = true,
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

    public function isIdempotent(): bool
    {
        return $this->idempotent;
    }
}
