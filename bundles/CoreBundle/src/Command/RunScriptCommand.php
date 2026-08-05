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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name:'pimcore:run-script',
    description: 'Run a specific PHP script in an initialized Pimcore environment'
)]
class RunScriptCommand extends AbstractCommand
{
    use DryRun;

    protected function configure(): void
    {
        $this
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
