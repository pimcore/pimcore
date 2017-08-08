<?php

namespace Pimcore\Extension\Bundle\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsExecuteDoctrineCommand;
use Pimcore\Extension\Bundle\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

class ExecuteCommand extends MigrationsExecuteDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('execute');
    }
}
