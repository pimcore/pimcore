<?php

namespace Pimcore\Migrations\Command;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Pimcore\Migrations\Command\Traits\PimcoreMigrationsConfiguration;
use Pimcore\Migrations\MigrationManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MarkAllDoneCommand extends AbstractCommand
{
    use PimcoreMigrationsConfiguration;

    protected $migrationManager;

    public function __construct(MigrationManager $migrationManager)
    {
        $this->migrationManager = $migrationManager;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('mark-all-done');
        $this->setDescription('Marks all available migrations as done, this is useful after upgrades from pre-5.4 versions or in certain deployment scenarios');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->migrationManager->getConfiguration($input->getOption('set'));
        $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());
        $latest = end($config->getMigrations());
        if ($latest) {
            $this->migrationManager->markVersionAsMigrated($latest);
        } else {
            $output->writeln('Nothing to do ...');
        }

        return 0;
    }
}
