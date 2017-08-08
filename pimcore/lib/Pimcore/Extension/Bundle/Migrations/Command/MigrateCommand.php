<?php

namespace Pimcore\Extension\Bundle\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsMigrateDoctrineCommand;
use Pimcore\Extension\Bundle\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

class MigrateCommand extends MigrationsMigrateDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('migrate');
    }
}
