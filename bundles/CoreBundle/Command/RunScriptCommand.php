<?php

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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class RunScriptCommand extends AbstractCommand
{
    use DryRun;

    protected function configure()
    {
        $this
            ->setName('pimcore:run-script')
            ->setDescription('Run a specific PHP script in an initialized Pimcore environment')
            ->addArgument(
                'script',
                InputArgument::REQUIRED,
                'Path to PHP script which should run'
            );

        $this->configureDryRunOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $script = $input->getArgument('script');

        if (!(preg_match('/\.php$/', $script) && file_exists($script))) {
            $output->writeln(sprintf(
                '<error>Script %s does not exist or doesn\'t have a .php extension</error>',
                $script
            ));

            return 1;
        }

        $output->writeln($this->dryRunMessage(sprintf('Running script <info>%s</info>', $script)));

        $scriptOutput = '';
        if (!$this->isDryRun()) {
            ob_start();

            include($script);
            $scriptOutput = ob_get_contents();

            ob_end_clean();
        }

        $scriptOutput = trim($scriptOutput);
        if (!empty($scriptOutput)) {
            $output->writeln("\n" . $scriptOutput . "\n");
        }

        $output->writeln($this->dryRunMessage('Done'));

        return 0;
    }
}
