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

namespace Pimcore\Bundle\CoreBundle\Command\Composer;

use Pimcore\Composer\Config\ConfigMerger;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class NormalizeConfigCommand extends AbstractCommand
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    public function __construct(Filesystem $fs, ConfigMerger $configMerger)
    {
        $this->fs           = $fs;
        $this->configMerger = $configMerger;

        parent::__construct();
    }

    protected function configure()
    {
        $defaultConfigFile = PIMCORE_PROJECT_ROOT . '/composer.json';
        $defaultConfigFile = rtrim($this->fs->makePathRelative($defaultConfigFile, getcwd()), '/');

        $this
            ->setName('pimcore:composer:normalize-config')
            ->setDescription('Runs a composer.json file through all configured normalizes and outputs the results')
            ->setHidden(true) // this is mainly for debugging/development and does not need to be exposed
            ->addArgument(
                'config-file',
                InputArgument::OPTIONAL,
                'Path to composer.json',
                $defaultConfigFile
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $input->getArgument('config-file');

        if (!file_exists($configFile)) {
            $this->io->error('File does not exist');

            return 1;
        }

        $content = file_get_contents($configFile);
        $json    = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->io->error(sprintf('JSON decode error: %s', json_last_error_msg()));

            return 2;
        }

        if (!is_array($json)) {
            $this->io->error('Decoded JSON is not an array');

            return 3;
        }

        $normalized = $this->configMerger->normalize($json);

        $this->io->writeln(json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->io->newLine();
    }
}
