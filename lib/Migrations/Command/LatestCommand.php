<?php

namespace Pimcore\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsLatestDoctrineCommand;
use Pimcore\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

class LatestCommand extends MigrationsLatestDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('latest');
    }
}
