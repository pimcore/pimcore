<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Console\Traits;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
trait DryRun
{
    /**
     * Configure --dry-run
     *
     *
     * @return $this
     */
    protected function configureDryRunOption(string $description = null): static
    {
        /** @var Command $command */
        $command = $this;

        if (null === $description) {
            $description = 'Simulate only (do not change anything)';
        }

        $command->addOption(
            'dry-run',
            'N',
            InputOption::VALUE_NONE,
            $description
        );

        return $this;
    }

    protected function isDryRun(): bool
    {
        /** @var Input $input */
        $input = $this->input;

        return (bool) $input->getOption('dry-run');
    }

    /**
     * Prefix message with DRY-RUN
     *
     *
     */
    protected function prefixDryRun(string $message, string $prefix = 'DRY-RUN'): string
    {
        return sprintf(
            '<bg=cyan;fg=white>%s</> %s',
            $prefix,
            $message
        );
    }

    /**
     * Prefix message with dry run if in dry-run mode
     *
     *
     */
    protected function dryRunMessage(string $message, string $prefix = 'DRY-RUN'): string
    {
        if ($this->isDryRun()) {
            $message = $this->prefixDryRun($message, $prefix);
        }

        return $message;
    }
}
