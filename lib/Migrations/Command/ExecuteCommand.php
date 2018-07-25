<?php

namespace Pimcore\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsExecuteDoctrineCommand;
use Pimcore\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

class ExecuteCommand extends MigrationsExecuteDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('execute');
    }
}
