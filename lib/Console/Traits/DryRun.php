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

namespace Pimcore\Console\Traits;

use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
trait DryRun
{
    /**
     * Configure --dry-run
     *
     * @return $this
     */
    protected function configureDryRunOption(?string $description = null): static
    {
        if (null === $description) {
            $description = 'Simulate only (do not change anything)';
        }

        $this->addOption(
            'dry-run',
            'N',
            InputOption::VALUE_NONE,
            $description
        );

        return $this;
    }

    protected function isDryRun(): bool
    {
        return (bool) $this->input->getOption('dry-run');
    }

    /**
     * Prefix message with DRY-RUN
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
     */
    protected function dryRunMessage(string $message, string $prefix = 'DRY-RUN'): string
    {
        if ($this->isDryRun()) {
            $message = $this->prefixDryRun($message, $prefix);
        }

        return $message;
    }
}
