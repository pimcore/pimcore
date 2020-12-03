<?php

namespace Pimcore\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsVersionDoctrineCommand;
use Pimcore\Migrations\Command\Traits\PimcoreMigrationsConfiguration;

/**
 * @deprecated will be removed in Pimcore 10, please use Doctrine Migrations commands directly
 */
class VersionCommand extends MigrationsVersionDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('version');
    }
}
