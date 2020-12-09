<?php

namespace Pimcore\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsLatestDoctrineCommand;
use Pimcore\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

/**
 * @deprecated will be removed in Pimcore 10, please use Doctrine Migrations commands directly
 */
class LatestCommand extends MigrationsLatestDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('latest');
    }
}
