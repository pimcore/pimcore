<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
}
