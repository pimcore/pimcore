<?php

namespace Pimcore\Extension\Bundle\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsExecuteDoctrineCommand;
use Doctrine\Bundle\MigrationsBundle\Command\MigrationsLatestDoctrineCommand;
use Pimcore\Extension\Bundle\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

class LatestCommand extends MigrationsLatestDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('latest');
    }
}
