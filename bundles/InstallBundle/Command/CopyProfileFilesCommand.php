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

namespace Pimcore\Bundle\InstallBundle\Command;

use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Install\Profile\FileInstaller;
use Pimcore\Install\Profile\ProfileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CopyProfileFilesCommand extends Command
{
    /**
     * @var PimcoreStyle
     */
    private $io;

    /**
     * @var ProfileLocator
     */
    private $profileLocator;

    /**
     * @var FileInstaller
     */
    private $fileInstaller;

    public function __construct(ProfileLocator $profileLocator, FileInstaller $fileInstaller)
    {
        $this->profileLocator = $profileLocator;
        $this->fileInstaller  = $fileInstaller;

        parent::__construct();
    }

    protected function configure()
    {
        $this->profileLocator->getProfiles();

        $this
            ->setName('pimcore:install:copy-profile-files')
            ->setDescription('Copies profile files into place. Does not run any install tasks, just copies/symlinks files defined in the manifest.')
            ->addArgument(
                'profile',
                InputArgument::REQUIRED,
                'The profile to install'
            )
            ->addOption(
                'overwrite-existing', 'o',
                InputOption::VALUE_NONE,
                'Overwrite existing files'
            )
            ->addOption(
                'symlink', 's',
                InputOption::VALUE_NONE,
                'Symlink install profile files instead of copying them. Will fall back to copy on Windows.'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new PimcoreStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $overwriteExisting = (bool)$input->getOption('overwrite-existing');
        $symlink           = (bool)$input->getOption('symlink');

        $profile = $this->profileLocator->getProfile($input->getArgument('profile'));

        $errors = $this->fileInstaller->installFiles($profile, $overwriteExisting, $symlink);

        if (0 === count($errors)) {
            $this->io->success(sprintf('Files for profile "%s" were successfully copied', $profile->getName()));

            return;
        }

        $this->io->error('Errors were encountered while copying files');
        $this->io->listing($errors);
    }
}
