<?php

namespace Pimcore\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsMigrateDoctrineCommand;
use Pimcore\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

/**
 * @deprecated will be removed in Pimcore 10, please use Doctrine Migrations commands directly
 */
class MigrateCommand extends MigrationsMigrateDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('migrate');
    }
}
