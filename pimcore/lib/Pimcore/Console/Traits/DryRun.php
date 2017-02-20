<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Traits;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;

trait DryRun
{
    /**
     * Configure --dry-run
     *
     * @param null $description
     * @return $this
     */
    protected function configureDryRunOption($description = null)
    {
        /** @var Command $command */
        $command = $this;

        if (null === $description) {
            $description = 'Simulate only (do not change anything)';
        }

        $command->addOption(
            'dry-run', 'N', InputOption::VALUE_NONE,
            $description
        );

        return $this;
    }

    /**
     * @return bool
     */
    protected function isDryRun()
    {
        /** @var Input $input */
        $input = $this->input;

        return (bool) $input->getOption('dry-run');
    }

    /**
     * Prefix message with DRY-RUN
     *
     * @param $message
     * @param string $prefix
     * @return string
     */
    protected function prefixDryRun($message, $prefix = 'DRY-RUN')
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
     * @param $message
     * @param string $prefix
     * @return string
     */
    protected function dryRunMessage($message, $prefix = 'DRY-RUN')
    {
        if ($this->isDryRun()) {
            $message = $this->prefixDryRun($message, $prefix);
        }

        return $message;
    }
}
