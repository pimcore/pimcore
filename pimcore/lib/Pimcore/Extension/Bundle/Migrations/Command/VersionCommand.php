<?php

namespace Pimcore\Extension\Bundle\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsStatusDoctrineCommand;
use Doctrine\Bundle\MigrationsBundle\Command\MigrationsVersionDoctrineCommand;
use Pimcore\Extension\Bundle\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

class VersionCommand extends MigrationsVersionDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('version');
    }
}
