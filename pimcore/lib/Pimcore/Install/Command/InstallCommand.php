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

namespace Pimcore\Install\Command;

use Pimcore\Config;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Install\Installer;
use Pimcore\Install\Profile\ProfileLocator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var ProfileLocator
     */
    private $profileLocator;

    /**
     * @var PimcoreStyle
     */
    private $io;

    /**
     * @var array
     */
    private $options;

    /**
     * @param Installer $installer
     * @param ProfileLocator $profileLocator
     */
    public function __construct(Installer $installer, ProfileLocator $profileLocator)
    {
        $this->installer      = $installer;
        $this->profileLocator = $profileLocator;

        parent::__construct();
    }

    private function getOptions()
    {
        if (null !== $this->options) {
            return $this->options;
        }

        $profiles = array_keys($this->profileLocator->getProfiles());

        $options = [
            'profile'           => [
                'description' => sprintf(
                    'The install profile to use. Available profiles: %s',
                    implode(', ', $profiles)
                ),
                'mode'        => InputOption::VALUE_REQUIRED,
                'default'     => 'empty',
                'choices'     => $profiles
            ],
            'admin-username'    => [
                'description' => 'Admin username',
                'mode'        => InputOption::VALUE_REQUIRED,
                'insecure'    => true,
            ],
            'admin-password'    => [
                'description'  => 'Admin password',
                'mode'         => InputOption::VALUE_REQUIRED,
                'insecure'     => true,
                'hidden-input' => true,
            ],
            'mysql-host-socket' => [
                'description' => 'MySQL Host or Socket',
                'mode'        => InputOption::VALUE_REQUIRED,
                'default'     => 'localhost'
            ],
            'mysql-username'    => [
                'description' => 'MySQL username',
                'mode'        => InputOption::VALUE_REQUIRED,
                'insecure'    => true,
            ],
            'mysql-password'    => [
                'description'  => 'MySQL password',
                'mode'         => InputOption::VALUE_REQUIRED,
                'insecure'     => true,
                'hidden-input' => true
            ],
            'mysql-database'    => [
                'description' => 'MySQL database',
                'mode'        => InputOption::VALUE_REQUIRED,
            ],
            'mysql-port'        => [
                'description' => 'MySQL Port (will be omitted if socket is set)',
                'mode'        => InputOption::VALUE_REQUIRED,
                'default'     => 3306
            ],
        ];

        foreach (array_keys($options) as $name) {
            $options[$name]['env'] = 'PIMCORE_INSTALL_' . strtoupper(str_replace('-', '_', $name));
        }

        $this->options = $options;

        return $options;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = $this->getOptions();

        $envVars = array_values(array_map(function ($config) {
            return $config['env'];
        }, $options));

        $description = 'Installs Pimcore with the given parameters. Every parameter will be prompted interactively or can also be set via env vars';

        $help = $description . ".\nAvailable env vars are:\n";
        foreach ($envVars as $envVar) {
            $help .= "\n" . sprintf('  <comment>*</comment> %s', $envVar);
        }

        $this
            ->setName('pimcore:install')
            ->setDescription($description)
            ->setHelp($help)
            ->addOption(
                'no-overwrite', null,
                InputOption::VALUE_NONE,
                'Do no overwrite existing files'
            )
            ->addOption(
                'symlink', null,
                InputOption::VALUE_NONE,
                'Symlink install profile files instead of copying them. Will fall back to copy on Windows.'
            )
            ->addOption(
                'ignore-existing-config', null,
                InputOption::VALUE_NONE,
                'Do not abort if a <comment>system.php</comment> file already exists'
            );

        foreach ($this->getOptions() as $name => $config) {
            $shortcut    = $config['shortcut'] ?? null;
            $mode        = $config['mode'] ?? null;
            $description = $config['description'] ?? '';
            $default     = $config['default'] ?? null;

            $this->addOption($name, $shortcut, $mode, $description, $default);
        }
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        /** @var Application $application */
        $application = $this->getApplication();

        if ('dev' !== $application->getKernel()->getEnvironment()) {
            throw new \RuntimeException('The installer can only be run in the dev environment');
        }

        // no installer if Pimcore is already installed
        $configFile = Config::locateConfigFile('system.php');
        if ($configFile && is_file($configFile) && !$input->getOption('ignore-existing-config')) {
            throw new \RuntimeException(sprintf('The system.php config file already exists in "%s"', $configFile));
        }

        $this->io = new PimcoreStyle($input, $output);

        foreach ($this->getOptions() as $name => $config) {
            $value          = $input->getOption($name);
            $isDefaultValue = isset($config['default']) && $value === $config['default'];

            // show warning for insecure options
            if ($value && ($config['insecure'] ?? false)) {
                $this->io->writeln([
                    sprintf(
                        '<comment>[WARNING]</comment> Using sensitive options (<comment>--%s</comment>) on the command line interface can be insecure.',
                        $name
                    ),
                    sprintf(
                        'Consider using the interactive prompt or the <comment>%s</comment> environment variable instead.',
                        $config['env']
                    )
                ]);

                $this->io->newLine();
            }

            // set option values from env vars
            if (!$value || $isDefaultValue) {
                if ($env = getenv($config['env'])) {
                    $input->setOption($name, $env);
                }
            }
        }
    }

    /**
     * Prompt options which are not set interactively
     *
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getOptions() as $name => $config) {
            $value          = $input->getOption($name);
            $isDefaultValue = isset($config['default']) && $value === $config['default'];

            if ($value && !$isDefaultValue) {
                continue;
            }

            $question = $config['prompt'] ?? $config['description'];

            if (isset($config['choices'])) {
                $value = $this->io->choice(
                    $question,
                    $config['choices'],
                    $value
                );
            } else {
                $validator = function ($answer) use ($name) {
                    if (empty($answer)) {
                        throw new \RuntimeException(sprintf('%s cannot be empty', $name));
                    }

                    return $answer;
                };

                if ($config['hidden-input'] ?? false) {
                    $question .= ' (input will be hidden)';
                    $value    = $this->io->askHidden($question, $validator);
                } else {
                    $value = $this->io->ask($question, $value, $validator);
                }
            }

            $input->setOption($name, $value);
        }
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params  = [];
        $missing = [];

        foreach (array_keys($this->getOptions()) as $name) {
            $value = $input->getOption($name);

            if ($value) {
                $param          = str_replace('-', '_', $name);
                $params[$param] = $value;
            } else {
                $missing[] = $name;
            }
        }

        if (count($missing) > 0) {
            $this->io->error('The following parameters are missing');
            $this->io->listing($missing);

            return 1;
        }

        if ($input->getOption('no-overwrite')) {
            $this->installer->setOverwriteExistingFiles(false);
        }

        if ($input->getOption('symlink')) {
            $this->installer->setSymlink(true);
        }

        $checkErrors = $this->installer->checkPrerequisites();
        $this->io->newLine();

        if (count($checkErrors) > 0) {
            $this->io->error('The following prerequisites failed');
            $this->io->listing($checkErrors);

            return 2;
        }

        $installErrors = $this->installer->install($params);
        $this->io->newLine();

        if (count($installErrors) > 0) {
            $this->io->error('The following errors were encountered during installation');
            $this->io->listing($installErrors);

            return 2;
        } else {
            $this->io->success('Pimcore was successfully installed');
        }
    }
}
