<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\Command;

use Pimcore\Bundle\InstallBundle\Collector\EnvVarReaderInterface;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommandsProviderInterface;
use Pimcore\Console\Style\PimcoreStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[AsCommand(
    'pimcore:install',
    'Install Pimcore with a profile-based configuration',
)]
final class InstallCommand extends Command
{
    private PimcoreStyle $io;

    private ?ProgressBar $progressBar = null;

    public function __construct(
        private readonly Installer $installer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EnvVarReaderInterface $envVarReader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                "Installs Pimcore using the specified install profile.\n\n"
                . "The profile defines which env var definitions (database, search engine, etc.),\n"
                . "bundles, data sources, and post-install commands are needed.\n\n"
                . "Every configuration value can be pre-set via environment variables for\n"
                . "non-interactive (CI) usage. See the profile documentation for the\n"
                . "supported env var names.\n\n"
                . "Use <comment>--skip-validation</comment> to skip all connection/format checks, or\n"
                . "<comment>--skip-validation=<key></comment> to skip specific definitions.\n\n"
                . "Admin credentials can be set via:\n"
                . "  <comment>PIMCORE_ADMIN_USER</comment>     Admin username\n"
                . "  <comment>PIMCORE_ADMIN_PASSWORD</comment>  Admin password\n"
            )
            ->addOption(
                'install-profile',
                null,
                InputOption::VALUE_REQUIRED,
                'FQCN of the install profile class (must implement InstallProfileInterface)',
            )
            ->addOption(
                'env-definition',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'FQCN of additional EnvVarDefinitionInterface implementations',
            )
            ->addOption(
                'post-install-commands',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'FQCN of PostInstallCommandsProviderInterface implementations',
            )
            ->addOption(
                'skip-validation',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Skip env var validation (no value = skip all, with value = skip by key/class name/FQCN)',
            )
            ->addOption(
                'admin-username',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin username (or set PIMCORE_ADMIN_USER env var)',
            )
            ->addOption(
                'admin-password',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin password (or set PIMCORE_ADMIN_PASSWORD env var)',
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new PimcoreStyle($input, $output);

        if ($input->getOption('admin-username') !== null) {
            $this->io->writeln(
                '<comment>[WARNING]</comment> Using <comment>--admin-username</comment>'
                . ' on the command line is insecure. Consider using the'
                . ' <comment>PIMCORE_ADMIN_USER</comment> env var instead.',
            );
            $this->io->newLine();
        }

        if ($input->getOption('admin-password') !== null) {
            $this->io->writeln(
                '<comment>[WARNING]</comment> Using <comment>--admin-password</comment>'
                . ' on the command line is insecure. Consider using the'
                . ' <comment>PIMCORE_ADMIN_PASSWORD</comment> env var instead.',
            );
            $this->io->newLine();
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $adminUsername = $this->resolveAdminUsername($input);
        if ($adminUsername === null) {
            $adminUsername = $this->io->ask('Admin Username', 'admin');
            $input->setOption('admin-username', $adminUsername);
        }

        $adminPassword = $this->resolveAdminPassword($input);
        if ($adminPassword === null) {
            $adminPassword = $this->io->askHidden('Admin Password (input will be hidden)');
            $input->setOption('admin-password', $adminPassword);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profileFqcn = $input->getOption('install-profile');
        if ($profileFqcn === null || $profileFqcn === '') {
            $this->io->error('The --install-profile option is required.');

            return Command::FAILURE;
        }

        $profile = $this->instantiateProfile($profileFqcn);
        if ($profile === null) {
            return Command::FAILURE;
        }

        $this->io->title(sprintf('Pimcore Installer — %s', $profile->getName()));
        $this->io->text($profile->getDescription());
        $this->io->newLine();

        $extraDefinitions = $this->instantiateExtraDefinitions(
            $input->getOption('env-definition'),
        );
        if ($extraDefinitions === null) {
            return Command::FAILURE;
        }

        $cliPostInstallProviders = $this->instantiatePostInstallProviders(
            $input->getOption('post-install-commands'),
        );
        if ($cliPostInstallProviders === null) {
            return Command::FAILURE;
        }

        $adminCredentials = $this->resolveAdminCredentials($input);
        if ($adminCredentials === null) {
            return Command::FAILURE;
        }

        $interactive = $input->isInteractive();
        $skipValidation = $input->getOption('skip-validation');

        if ($interactive && !$this->io->confirm(
            'This will install Pimcore with the given settings. Do you want to continue?',
        )) {
            return Command::SUCCESS;
        }

        $this->setupProgressBar($output);

        $parameterCollector = new ParameterCollector($this->envVarReader);
        $projectRoot = PIMCORE_PROJECT_ROOT;

        $phase1Errors = $this->installer->runPhaseOne(
            $profile,
            $extraDefinitions,
            $skipValidation,
            $adminCredentials,
            $parameterCollector,
            $this->io,
            $interactive,
            $projectRoot,
        );

        if ($phase1Errors !== []) {
            $this->finishProgressBar();
            $this->io->error('Configuration failed with the following errors:');
            $this->io->listing($phase1Errors);

            return Command::FAILURE;
        }

        $phase2Errors = $this->installer->runPhaseTwo(
            $profile,
            $cliPostInstallProviders,
            $adminCredentials,
            $this->io,
            $projectRoot,
        );

        $this->finishProgressBar();

        if ($phase2Errors !== []) {
            $this->io->error('Installation encountered the following errors:');
            $this->io->listing($phase2Errors);

            return Command::FAILURE;
        }

        $this->io->success('Pimcore was successfully installed!');

        return Command::SUCCESS;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $expectedType
     *
     * @return T|null null on error (error already displayed)
     */
    private function instantiateAndValidate(
        string $fqcn,
        string $expectedType,
        string $typeLabel,
    ): ?object {
        if (!class_exists($fqcn)) {
            $this->io->error(sprintf(
                '%s class "%s" does not exist. Is it autoloadable?',
                $typeLabel,
                $fqcn,
            ));

            return null;
        }

        try {
            $instance = new $fqcn();
        } catch (\Throwable $e) {
            $this->io->error(sprintf(
                'Failed to instantiate %s "%s": %s',
                lcfirst($typeLabel),
                $fqcn,
                $e->getMessage(),
            ));

            return null;
        }

        if (!$instance instanceof $expectedType) {
            $this->io->error(sprintf(
                '%s class "%s" must implement %s.',
                $typeLabel,
                $fqcn,
                $expectedType,
            ));

            return null;
        }

        return $instance;
    }

    private function instantiateProfile(string $fqcn): ?InstallProfileInterface
    {
        return $this->instantiateAndValidate(
            $fqcn,
            InstallProfileInterface::class,
            'Profile',
        );
    }

    /**
     * @param list<string> $fqcns
     *
     * @return list<EnvVarDefinitionInterface>|null null on error
     */
    private function instantiateExtraDefinitions(array $fqcns): ?array
    {
        $definitions = [];

        foreach ($fqcns as $fqcn) {
            $instance = $this->instantiateAndValidate(
                $fqcn,
                EnvVarDefinitionInterface::class,
                'Env var definition',
            );

            if ($instance === null) {
                return null;
            }

            $definitions[] = $instance;
        }

        return $definitions;
    }

    /**
     * @param list<string> $fqcns
     *
     * @return list<PostInstallCommandsProviderInterface>|null null on error
     */
    private function instantiatePostInstallProviders(array $fqcns): ?array
    {
        $providers = [];

        foreach ($fqcns as $fqcn) {
            $instance = $this->instantiateAndValidate(
                $fqcn,
                PostInstallCommandsProviderInterface::class,
                'Post-install commands provider',
            );

            if ($instance === null) {
                return null;
            }

            $providers[] = $instance;
        }

        return $providers;
    }

    /**
     * @return array{username: string, password: string}|null null on error
     */
    private function resolveAdminCredentials(InputInterface $input): ?array
    {
        $username = $this->resolveAdminUsername($input);
        $password = $this->resolveAdminPassword($input);

        if ($username === null || $username === '') {
            $this->io->error(
                'Admin username is required. Use --admin-username or set'
                . ' the PIMCORE_ADMIN_USER environment variable.',
            );

            return null;
        }

        if ($password === null || $password === '') {
            $this->io->error(
                'Admin password is required. Use --admin-password or set'
                . ' the PIMCORE_ADMIN_PASSWORD environment variable.',
            );

            return null;
        }

        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    private function resolveOptionOrEnv(
        InputInterface $input,
        string $optionName,
        string $envVarName,
    ): ?string {
        $value = $input->getOption($optionName);
        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->envVarReader->get($envVarName);
    }

    private function resolveAdminUsername(InputInterface $input): ?string
    {
        return $this->resolveOptionOrEnv($input, 'admin-username', 'PIMCORE_ADMIN_USER');
    }

    private function resolveAdminPassword(InputInterface $input): ?string
    {
        return $this->resolveOptionOrEnv($input, 'admin-password', 'PIMCORE_ADMIN_PASSWORD');
    }

    private function setupProgressBar(OutputInterface $output): void
    {
        $this->progressBar = new ProgressBar($output);
        $this->progressBar->setMessage('Starting installation...');
        $this->progressBar->setFormat(
            "<info>%message%</info>\n\n %current%/%max% [%bar%] %percent:3s%%\n",
        );

        $this->eventDispatcher->addListener(
            InstallEvents::EVENT_NAME_STEP,
            function (InstallerStepEvent $event): void {
                if ($this->progressBar === null) {
                    return;
                }

                $this->progressBar->setMaxSteps($event->getTotalSteps());
                $this->progressBar->setMessage($event->getMessage());
                $this->progressBar->setProgress($event->getStep());
            },
        );
    }

    private function finishProgressBar(): void
    {
        if ($this->progressBar !== null) {
            $this->progressBar->finish();
            $this->io->newLine(2);
            $this->progressBar = null;
        }
    }
}
