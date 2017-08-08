<?php

namespace Pimcore\Extension\Bundle\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsStatusDoctrineCommand;
use Pimcore\Extension\Bundle\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

class StatusCommand extends MigrationsStatusDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('status');
    }
}
