<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\InstallBundle\Command;

use Pimcore\Bundle\InstallBundle\Event\BundleSetupEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Console\ConsoleOutputDecorator;
use Pimcore\Console\Style\PimcoreStyle;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @method Application getApplication()
 *
 * @internal
 */
#[AsCommand(
    name: 'pimcore:install',
    description: 'Installs Pimcore with the given parameters. Every parameter will be prompted interactively or can also be set via env vars'
)]
class InstallCommand extends Command
{
    private Installer $installer;

    private EventDispatcherInterface $eventDispatcher;

    private PimcoreStyle $io;

    private ?array $options = null;

    public function __construct(
        Installer $installer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->installer = $installer;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct();
    }

    private function getOptions(): array
    {
        if (null !== $this->options) {
            return $this->options;
        }

        $options = [

            'admin-username' => [
                'description' => 'Admin username',
                'mode' => InputOption::VALUE_REQUIRED,
                'insecure' => true,
            ],
            'admin-password' => [
                'description' => 'Admin password',
                'mode' => InputOption::VALUE_REQUIRED,
                'insecure' => true,
                'hidden-input' => true,
            ],
            'mysql-host-socket' => [
                'description' => 'MySQL Host or Socket',
                'mode' => InputOption::VALUE_REQUIRED,
                'default' => 'localhost',
                'group' => 'db_credentials',
            ],
            'mysql-username' => [
                'description' => 'MySQL username',
                'mode' => InputOption::VALUE_REQUIRED,
                'insecure' => true,
                'group' => 'db_credentials',
            ],
            'mysql-password' => [
                'description' => 'MySQL password',
                'mode' => InputOption::VALUE_OPTIONAL,
                'insecure' => true,
                'hidden-input' => true,
                'group' => 'db_credentials',
            ],
            'mysql-database' => [
                'description' => 'MySQL database',
                'mode' => InputOption::VALUE_REQUIRED,
                'group' => 'db_credentials',
            ],
            'mysql-port' => [
                'description' => 'MySQL Port (will be omitted if socket is set)',
                'mode' => InputOption::VALUE_REQUIRED,
                'default' => 3306,
                'group' => 'db_credentials',
            ],
            'mysql-ssl-cert-path' => [
                'description' => 'MySQL SSL certificate path (if empty non-ssl connection assumed)',
                'mode' => InputOption::VALUE_OPTIONAL,
                'default' => '',
                'group' => 'db_credentials',
            ],
            'install-bundles' => [
                'description' => sprintf('Installable bundles: %s', $this->generateBundleDescription()),
                'mode' => InputOption::VALUE_OPTIONAL,
                'default' => false,
                'group' => 'bundles',
            ],
        ];

        foreach (array_keys($options) as $name) {
            $options[$name]['env'] = 'PIMCORE_INSTALL_' . strtoupper(str_replace('-', '_', $name));
        }

        $this->options = $options;

        return $options;
    }

    protected function configure(): void
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
            ->setHelp($help)
            ->addOption(
                'skip-database-config',
                null,
                InputOption::VALUE_NONE,
                'Do not write a database config file: <comment>database.yaml</comment>'
            )->addOption(
                'skip-database-structure',
                null,
                InputOption::VALUE_NONE,
                'Skip creation of database structure during install'
            )->addOption(
                'skip-database-data',
                null,
                InputOption::VALUE_NONE,
                'Skip importing of any data into database'
            )->addOption(
                'skip-database-data-dump',
                null,
                InputOption::VALUE_NONE,
                'Skipping importing of provided data dumps into database (if available). Only imports needed base data.'
            )->addOption(
                'only-steps',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify a comma separated limited list of steps that should run. Available steps: ' . implode(', ', $this->installer->getRunInstallSteps())
            );

        foreach ($this->getOptions() as $name => $config) {
            $shortcut = $config['shortcut'] ?? null;
            $mode = $config['mode'] ?? null;
            $description = $config['description'] ?? '';
            $default = $config['default'] ?? null;

            $this->addOption($name, $shortcut, $mode, $description, $default);
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($onlySteps = $input->getOption('only-steps')) {
            $onlySteps = array_map('trim', explode(',', $onlySteps));
            $this->installer->setRunInstallSteps($onlySteps);
        }

        if ($input->getOption('skip-database-config')) {
            $this->installer->setSkipDatabaseConfig(true);
        }

        //check skipping database creation or database data
        if ($input->getOption('skip-database-structure')) {
            $this->installer->setCreateDatabaseStructure(false);
        }
        if ($input->getOption('skip-database-data')) {
            $this->installer->setImportDatabaseData(false);
        }
        if ($input->getOption('skip-database-data-dump')) {
            $this->installer->setImportDatabaseDataDump(false);
        }
        $bundleOption = $input->getOption('install-bundles');
        if (false !== $bundleOption) {
            $bundleSetupEvent = $this->installer->dispatchBundleSetupEvent();

            if (null === $bundleOption) {
                $bundles = [];
            } else {
                $bundles = explode(',', $bundleOption);
            }

            $installableBundles = $bundleSetupEvent->getInstallableBundles($bundles);
            $this->installer->setBundlesToInstall($installableBundles, $bundleSetupEvent->getAvailableBundles(), $bundleSetupEvent->getExcludeBundlesFromPhpBundles());
        }

        $this->io = new PimcoreStyle($input, $output);

        foreach ($this->getOptions() as $name => $config) {
            if (!$this->installerNeedsOption($config)) {
                continue;
            }

            $value = $input->getOption($name);
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
                    ),
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
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->getOptions() as $name => $config) {
            if (!$this->installerNeedsOption($config)) {
                continue;
            }

            $value = $input->getOption($name);
            $isDefaultValue = isset($config['default']) && $value === $config['default'];

            if ($value || $isDefaultValue) {
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
                        throw new RuntimeException(sprintf('%s cannot be empty', $name));
                    }

                    return $answer;
                };

                if ($config['hidden-input'] ?? false) {
                    $question .= ' (input will be hidden)';
                    $value = $this->io->askHidden($question, $validator);
                } else {
                    $value = $this->io->ask($question, $value, $validator);
                }
            }

            $input->setOption($name, $value);
        }
    }

    private function installerNeedsOption(array $config): bool
    {
        if ('db_credentials' === ($config['group'] ?? null) && !$this->installer->needsDbCredentials()) {
            return false;
        }

        if ('bundles' === ($config['group'] ?? null)) {
            return false;
        }

        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // dispatch a bundle config event here to manually add/remove bundles/recommendations
        $bundleSetupEvent = $this->installer->dispatchBundleSetupEvent();

        if (!empty($bundleSetupEvent->getBundles()) && !$input->getOption('install-bundles') && $input->isInteractive() && $this->io->confirm(sprintf('Do you want to install bundles? We recommend %s.', implode(', ', $bundleSetupEvent->getRecommendedBundles())), false)) {
            $bundles = $this->io->choice('Which bundle(s) do you want to install? You can choose multiple e.g. 0,1,2,3 or just hit enter to install recommended bundles', array_keys($bundleSetupEvent->getBundles()), $this->getRecommendBundles($bundleSetupEvent), true);
            $installableBundles = $bundleSetupEvent->getInstallableBundles($bundles);
            $this->installer->setBundlesToInstall($installableBundles, $bundleSetupEvent->getAvailableBundles(), $bundleSetupEvent->getExcludeBundlesFromPhpBundles());
        }

        if ($input->isInteractive() && !$this->io->confirm('This will install Pimcore with the given settings. Do you want to continue?')) {
            return 0;
        }

        $params = [];
        $missing = [];

        foreach ($this->getOptions() as $name => $config) {
            if (!$this->installerNeedsOption($config)) {
                continue;
            }

            $value = $input->getOption($name);

            // Empty MySQL password allowed, empty ssl cert path means it is not used
            if ($value || $name === 'mysql-password' || $name === 'mysql-ssl-cert-path' || $config['mode'] === InputOption::VALUE_NONE) {
                $param = str_replace('-', '_', $name);
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

        $checkErrors = $this->installer->checkPrerequisites();
        $this->io->newLine();

        if (count($checkErrors) > 0) {
            $this->io->error('The following prerequisites failed');
            $this->io->listing($checkErrors);

            return 2;
        }

        $this->io->writeln(sprintf(
            'Running installation. You can find a detailed install log in <comment>var/installer/log/%s.log</comment>',
            $this->getApplication()->getKernel()->getEnvironment()
        ));

        $this->io->newLine();

        $progressBar = new ProgressBar($output, $this->installer->getStepEventCount());
        $progressBar->setMessage('Starting the install process...');
        $progressBar->setFormat("<info>%message%</info>\n\n %current%/%max% [%bar%] %percent:3s%%\n");
        $progressBar->start();

        $this->eventDispatcher->addListener(
            InstallEvents::EVENT_NAME_STEP,
            function (InstallerStepEvent $event) use ($progressBar) {
                $progressBar->setMessage($event->getMessage());
                $progressBar->advance();
            }
        );

        // catch installer output in a buffered output and write results after progress bar is finished
        $installerOutput = new BufferedOutput($output->getVerbosity(), $output->isDecorated(), $output->getFormatter());
        $installerErrorOutput = new BufferedOutput($output->getVerbosity(), $output->isDecorated(), $output->getFormatter());

        $this->installer->setCommandLineOutput(new PimcoreStyle(
            $input,
            new ConsoleOutputDecorator($installerOutput, $installerErrorOutput)
        ));

        $installErrors = $this->installer->install($params);

        if (0 === count($installErrors)) {
            $progressBar->finish();
        }

        $this->io->newLine(2);

        $this->writeInstallerOutputResults($installerOutput, $installerErrorOutput);

        if (count($installErrors) > 0) {
            $this->io->error('The following errors were encountered during installation');
            $this->io->listing($installErrors);

            return 2;
        }

        $this->io->success('Pimcore was successfully installed');

        return 0;
    }

    private function writeInstallerOutputResults(BufferedOutput $output, BufferedOutput $errorOutput): void
    {
        $outputResults = $output->fetch();
        if (!empty($outputResults)) {
            $this->io->write($outputResults);
        }

        $errorResults = $errorOutput->fetch();
        if (!empty($errorResults)) {
            $this->io->getErrorStyle()->write($errorResults);
            $this->io->getErrorStyle()->newLine(2);
        }
    }

    private function generateBundleDescription(): string
    {
        return implode(',', array_keys(Installer::INSTALLABLE_BUNDLES));
    }

    private function getRecommendBundles(BundleSetupEvent $bundleSetupEvent): string
    {
        $installableBundleKeys = array_keys($bundleSetupEvent->getBundles());
        $recommendedBundles = [];
        foreach ($bundleSetupEvent->getRecommendedBundles() as $recBundle) {
            $recommendedBundles[] = array_search($recBundle, $installableBundleKeys);
        }

        return implode(',', $recommendedBundles);
    }
}
