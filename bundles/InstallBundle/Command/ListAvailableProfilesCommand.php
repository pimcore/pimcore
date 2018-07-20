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
use Pimcore\Install\Profile\ProfileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListAvailableProfilesCommand extends Command
{
    /**
     * @var PimcoreStyle
     */
    private $io;

    /**
     * @var ProfileLocator
     */
    private $profileLocator;

    public function __construct(ProfileLocator $profileLocator)
    {
        $this->profileLocator = $profileLocator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pimcore:install:list-profiles')
            ->setDescription('Lists available install profiles');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new PimcoreStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profiles = $this->profileLocator->getProfiles();

        if (empty($profiles)) {
            $this->io->warning('No profiles were found');

            return;
        }

        $this->io->section('The following profiles are available:');

        $rows = [];
        foreach ($profiles as $profile) {
            $rows[] = [
                $profile->getId(),
                $profile->getName()
            ];
        }

        $this->io->table(['ID', 'Name'], $rows);
    }
}
