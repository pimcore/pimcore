<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Command\Bundle;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends AbstractBundleCommand
{
    protected function configure()
    {
        $this
            ->setName($this->buildName('install'))
            ->configureDescriptionAndHelp('Installs a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to install')
            ->configureFailWithoutErrorOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bm     = $this->getBundleManager();
        $bundle = $this->getBundle();

        // sets up installer with console output writer
        $this->setupInstaller($bundle);

        try {
            $bm->install($bundle);

            $this->io->success(sprintf('Bundle "%s" was successfully installed', $bundle->getName()));
        } catch (\Exception $e) {
            return $this->handlePrerequisiteError($e->getMessage());
        }
    }
}
