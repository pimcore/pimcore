<?php

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Update;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            )
            ->addOption(
                'cleanup',
                'c',
                InputOption::VALUE_NONE,
                'Clean up opcache and temporary DB tables after script (useful if running update scripts)'
            );

        $this->configureDryRunOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        if ($input->getOption('cleanup')) {
            $output->writeln($this->dryRunMessage('Cleaning up'));

            Update::clearOPCaches();
            Update::cleanup();
        }

        $output->writeln($this->dryRunMessage('Done'));
    }
}
