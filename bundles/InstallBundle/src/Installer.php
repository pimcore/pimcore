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

namespace Pimcore\Bundle\InstallBundle;

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Bundle\InstallBundle\BundleConfig\BundleInstaller;
use Pimcore\Bundle\InstallBundle\Checkpoint\InstallerCheckpoint;
use Pimcore\Bundle\InstallBundle\Console\ConsoleCommandRunner;
use Pimcore\Bundle\InstallBundle\Database\DatabaseSetup;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\Env\EnvWriter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Bundle\InstallBundle\Profile\DataSource\DataSourceInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Bundle\InstallBundle\PostInstall\PostInstallRunner;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommandsProviderInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallHookInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallContext;
use Pimcore\Config;
use Pimcore\Tool\AssetsInstaller;
use Pimcore\Tool\Authentication;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Throwable;

/**
 * Profile-first installer orchestrator.
 *
 * Phase 1 (InstallerKernel): collect parameters, validate connections, write .env.local
 * Phase 2 (real kernel): database setup, bundle install, post-install commands
 *
 * @internal
 */
class Installer
{
    public const string NEEDS_INSTALL_MARKER = PIMCORE_PRIVATE_VAR . '/config/needs-install.lock';

    /** Phase 2 step constants — used for checkpoint tracking */
    private const int STEP_SETUP_DATABASE = 12;

    private const int STEP_IMPORT_DATA_SOURCE = 13;

    private const int STEP_CREATE_ADMIN = 14;

    private const int STEP_REGISTER_BUNDLES = 15;

    private const int STEP_REBOOT_KERNEL = 16;

    private const int STEP_INSTALL_BUNDLES = 17;

    private const int STEP_INSTALL_ASSETS = 18;

    private const int STEP_REBUILD_CLASSES = 19;

    private const int STEP_MARK_MIGRATIONS = 20;

    private const int STEP_RUN_POST_INSTALL_COMMANDS = 21;

    private const int STEP_RUN_MAINTENANCE = 22;

    private const int STEP_RUN_PROFILE_POST_INSTALL = 23;

    private int $stepCounter = 0;

    private int $totalSteps = 0;

    /**
     * @param (\Closure(string): InstallerCheckpoint)|null $checkpointFactory
     * @param (\Closure(string): EnvWriter)|null $envWriterFactory
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DatabaseSetup $databaseSetup,
        private readonly DefinitionResolver $definitionResolver,
        private readonly ConsoleCommandRunner $commandRunner,
        private readonly BundleInstaller $bundleInstaller,
        private readonly PostInstallRunner $postInstallRunner,
        private readonly ?\Closure $checkpointFactory = null,
        private readonly ?\Closure $envWriterFactory = null,
    ) {
    }

    private function createCheckpoint(string $projectRoot): InstallerCheckpoint
    {
        if ($this->checkpointFactory !== null) {
            return ($this->checkpointFactory)($projectRoot);
        }

        return new InstallerCheckpoint($projectRoot);
    }

    private function createEnvWriter(string $envFilePath): EnvWriter
    {
        if ($this->envWriterFactory !== null) {
            return ($this->envWriterFactory)($envFilePath);
        }

        return new EnvWriter($envFilePath);
    }

    /**
     * Run Phase 1: collect config, validate, write .env.local
     *
     * @param InstallProfileInterface $profile The install profile
     * @param list<EnvVarDefinitionInterface> $extraDefinitions CLI-provided definitions
     * @param list<string> $skipKeys Keys of definitions to skip
     * @param array{username: string, password: string} $adminCredentials
     * @param bool $interactive Whether to prompt interactively
     * @param string $projectRoot Absolute path to project root
     *
     * @throws Throwable from definition validation or env writing
     * @throws IOException from filesystem operations
     *
     * @return list<string> errors (empty = success)
     */
    public function runPhaseOne(
        InstallProfileInterface $profile,
        array $extraDefinitions,
        array $skipKeys,
        array $adminCredentials,
        ParameterCollector $parameterCollector,
        SymfonyStyle $io,
        bool $interactive,
        string $projectRoot,
    ): array {
        $activeDefinitions = $this->definitionResolver->mergeDefinitions(
            $profile->getEnvVarDefinitions(),
            $extraDefinitions,
        );

        $categoryErrors = $this->definitionResolver->validateDefinitionCategories($activeDefinitions);
        if ($categoryErrors !== []) {
            return $categoryErrors;
        }

        $skipErrors = $this->definitionResolver->applySkipFlags($activeDefinitions, $skipKeys);
        if ($skipErrors !== []) {
            return $skipErrors;
        }

        $this->definitionResolver->warnOnMissingBundles($profile, $io, $projectRoot);

        $bundleErrors = $this->definitionResolver->validateProfileBundles($profile);
        if ($bundleErrors !== []) {
            return $bundleErrors;
        }

        $this->definitionResolver->displayDefinitionSummary($activeDefinitions, $io);

        $result = $this->collectAndValidateDefinitions(
            $activeDefinitions,
            $parameterCollector,
            $io,
            $interactive,
        );

        if ($result['errors'] !== []) {
            return $result['errors'];
        }

        $credentialErrors = $this->validateAdminCredentials($adminCredentials);
        if ($credentialErrors !== []) {
            return $credentialErrors;
        }

        $this->dispatchStep('write_env', 'Writing .env.local...');
        $writeErrors = $this->writeEnvLocal($result['resolved'], $projectRoot, $io);

        if ($writeErrors !== []) {
            return $writeErrors;
        }

        $io->text('  <info>✓</info> .env.local written');

        return [];
    }

    /**
     * Run Phase 2: database setup, bundle install, post-install commands.
     *
     * Boots the real kernel, performs all installation steps with checkpoint tracking.
     *
     * @param InstallProfileInterface $profile The install profile
     * @param list<PostInstallCommandsProviderInterface> $cliPostInstallProviders CLI post-install providers
     * @param array{username: string, password: string} $adminCredentials
     * @param string $projectRoot Absolute path to project root
     *
     * @throws Throwable from any installation step
     * @throws IOException from filesystem operations
     *
     * @return list<string> errors (empty = success)
     */
    public function runPhaseTwo(
        InstallProfileInterface $profile,
        array $cliPostInstallProviders,
        array $adminCredentials,
        SymfonyStyle $io,
        string $projectRoot,
    ): array {
        $checkpoint = $this->createCheckpoint($projectRoot);
        $completedStep = $checkpoint->getCompletedStep();

        if ($completedStep !== null) {
            $io->note(sprintf(
                'Resuming from step %d (previous run detected)',
                $completedStep + 1,
            ));
        }

        $this->calculateTotalSteps($profile);
        $errors = [];

        $this->dispatchStep('boot_kernel', 'Booting application kernel...');
        $kernel = $this->bootRealKernel($projectRoot);

        // Setup database
        $hasDataSource = $profile->getDataSource() !== null;
        $error = $this->executeStep(
            self::STEP_SETUP_DATABASE,
            $completedStep,
            $checkpoint,
            'setup_database',
            'Setting up database...',
            'Schema created',
            function () use ($kernel, $hasDataSource): void {
                $db = $this->getDatabaseConnection($kernel);
                $this->setupDatabase($db, $hasDataSource);
            },
        );
        if ($error !== null) {
            return [$error];
        }

        // Import data source (before admin creation — dumps may contain user rows)
        $dataSource = $profile->getDataSource();
        if ($dataSource !== null) {
            $error = $this->executeStep(
                self::STEP_IMPORT_DATA_SOURCE,
                $completedStep,
                $checkpoint,
                'import_data',
                'Importing data...',
                $dataSource->getLabel(),
                function () use ($dataSource, $kernel, $io): void {
                    $db = $this->getDatabaseConnection($kernel);
                    $this->importDataSource($dataSource, $db, $io);
                },
            );
            if ($error !== null) {
                return [$error];
            }
        }

        // Create/update admin user (after data import to overwrite any dump admin)
        $error = $this->executeStep(
            self::STEP_CREATE_ADMIN,
            $completedStep,
            $checkpoint,
            'create_admin',
            'Creating admin user...',
            'Admin user created',
            function () use ($kernel, $adminCredentials): void {
                $db = $this->getDatabaseConnection($kernel);
                $this->createOrUpdateAdminUser($db, $adminCredentials);
            },
        );
        if ($error !== null) {
            return [$error];
        }

        // Register bundles
        $error = $this->executeStep(
            self::STEP_REGISTER_BUNDLES,
            $completedStep,
            $checkpoint,
            'register_bundles',
            'Registering bundles...',
            sprintf('%d bundles registered', count($profile->getBundles())),
            function () use ($profile): void {
                $this->bundleInstaller->registerBundles($profile);
            },
        );
        if ($error !== null) {
            return [$error];
        }

        // Reboot kernel (bundles.php changed)
        $error = $this->executeStep(
            self::STEP_REBOOT_KERNEL,
            $completedStep,
            $checkpoint,
            'reboot_kernel',
            'Rebooting kernel...',
            'Kernel rebooted',
            function () use (&$kernel, $projectRoot): void {
                $kernel->shutdown();
                $this->clearKernelCacheDir($kernel);
                $kernel = $this->bootRealKernel($projectRoot);
            },
        );
        if ($error !== null) {
            return [$error];
        }

        // Install bundles
        $error = $this->executeStep(
            self::STEP_INSTALL_BUNDLES,
            $completedStep,
            $checkpoint,
            'install_bundles',
            'Installing bundles...',
            'Bundles installed',
            function () use ($profile, $io): void {
                $this->bundleInstaller->installBundles($profile, $io);
            },
        );
        if ($error !== null) {
            return [$error];
        }

        // Install assets
        $error = $this->executeStep(
            self::STEP_INSTALL_ASSETS,
            $completedStep,
            $checkpoint,
            'install_assets',
            'Installing assets...',
            'Assets installed',
            function () use ($kernel): void {
                $this->installAssets($kernel);
            },
        );
        if ($error !== null) {
            return [$error];
        }

        // Rebuild classes (non-fatal)
        $error = $this->executeStep(
            self::STEP_REBUILD_CLASSES,
            $completedStep,
            $checkpoint,
            'rebuild_classes',
            'Rebuilding class definitions...',
            'Classes rebuilt',
            function (): void {
                $this->commandRunner->rebuildClasses();
            },
            false,
        );
        if ($error !== null) {
            $errors[] = $error;
        }

        // Mark migrations as done (non-fatal)
        $error = $this->executeStep(
            self::STEP_MARK_MIGRATIONS,
            $completedStep,
            $checkpoint,
            'mark_migrations',
            'Marking migrations as done...',
            'Migrations marked',
            function (): void {
                $this->commandRunner->markMigrationsAsDone();
            },
            false,
        );
        if ($error !== null) {
            $errors[] = $error;
        }

        // Collect and run post-install commands
        $postInstallCommands = $this->postInstallRunner->collectPostInstallCommands(
            $profile,
            $cliPostInstallProviders,
            $kernel,
        );

        if ($postInstallCommands !== []) {
            $error = $this->executeStep(
                self::STEP_RUN_POST_INSTALL_COMMANDS,
                $completedStep,
                $checkpoint,
                'post_install_commands',
                'Running post-install commands...',
                sprintf('%d commands executed', count($postInstallCommands)),
                function () use ($postInstallCommands, $io): void {
                    $this->postInstallRunner->runPostInstallCommands($postInstallCommands, $io);
                },
            );
            if ($error !== null) {
                return array_merge($errors, [$error]);
            }
        }

        // Run pimcore:maintenance (non-fatal)
        $error = $this->executeStep(
            self::STEP_RUN_MAINTENANCE,
            $completedStep,
            $checkpoint,
            'run_maintenance',
            'Running maintenance...',
            'Maintenance completed',
            function (): void {
                $this->commandRunner->runMaintenance();
            },
            false,
        );
        if ($error !== null) {
            $errors[] = $error;
        }

        // Run profile postInstall() if profile implements the optional hook
        if ($profile instanceof PostInstallHookInterface) {
            $error = $this->executeStep(
                self::STEP_RUN_PROFILE_POST_INSTALL,
                $completedStep,
                $checkpoint,
                'profile_post_install',
                'Running profile post-install...',
                'Profile postInstall() completed',
                function () use ($kernel, $profile, $io): void {
                    $db = $this->getDatabaseConnection($kernel);
                    $context = new PostInstallContext($db, $io);
                    $profile->postInstall($context);
                },
            );
            if ($error !== null) {
                return array_merge($errors, [$error]);
            }
        }

        // Finalize
        $this->dispatchStep('finalize', 'Finalizing installation...');
        $this->clearKernelCacheDir($kernel);
        $this->cleanupNeedsInstallMarker();
        $checkpoint->remove();

        return $errors;
    }

    /**
     * Collect parameters for each definition and validate them, with retry support
     * for interactive mode.
     *
     * @param array<string, EnvVarDefinitionInterface> $activeDefinitions
     *
     * @return array{
     *     resolved: array<string, array{definition: EnvVarDefinitionInterface, values: array<string, string>}>,
     *     errors: list<string>,
     * }
     */
    private function collectAndValidateDefinitions(
        array $activeDefinitions,
        ParameterCollector $parameterCollector,
        SymfonyStyle $io,
        bool $interactive,
    ): array {
        $resolvedByDefinition = [];
        $errors = [];
        $this->dispatchStep('collect_validate', 'Collecting and validating configuration...');

        foreach ($activeDefinitions as $key => $definition) {
            $io->section($definition->getLabel());

            $maxRetries = $interactive ? 3 : 1;
            $attempt = 0;
            $collectedValues = null;

            while ($attempt < $maxRetries) {
                $attempt++;

                $collectedValues = $parameterCollector->collect($definition, $io, $interactive);
                if ($collectedValues === null) {
                    $io->text('  Skipped');
                    break;
                }

                $validationErrors = $definition->validate($collectedValues);

                if ($validationErrors === []) {
                    $io->text('  <info>✓</info> Validation successful');
                    $resolvedByDefinition[$key] = [
                        'definition' => $definition,
                        'values' => $collectedValues,
                    ];
                    break;
                }

                foreach ($validationErrors as $error) {
                    $io->error(sprintf('%s: %s', $definition->getLabel(), $error));
                }

                if ($interactive && $attempt < $maxRetries) {
                    if (!$io->confirm('Retry?', true)) {
                        $errors = array_merge($errors, array_map(
                            static fn (string $err) => sprintf(
                                '%s: %s',
                                $definition->getLabel(),
                                $err,
                            ),
                            $validationErrors,
                        ));
                        break;
                    }
                } else {
                    $errors = array_merge($errors, array_map(
                        static fn (string $err) => sprintf(
                            '%s: %s',
                            $definition->getLabel(),
                            $err,
                        ),
                        $validationErrors,
                    ));
                }
            }
        }

        return ['resolved' => $resolvedByDefinition, 'errors' => $errors];
    }

    /**
     * @param array{username: string, password: string} $credentials
     *
     * @return list<string> errors
     */
    private function validateAdminCredentials(array $credentials): array
    {
        $errors = [];

        $username = $credentials['username'];
        $password = $credentials['password'];

        if (strlen($username) < 4) {
            $errors[] = 'Admin username must be at least 4 characters';
        }

        if (strlen($password) < 4) {
            $errors[] = 'Admin password must be at least 4 characters';
        }

        return $errors;
    }

    /**
     * Resolve env vars from each definition and write to .env.local.
     *
     * @param array<string, array{
     *     definition: EnvVarDefinitionInterface,
     *     values: array<string, string>,
     * }> $resolvedByDefinition
     *
     * @return list<string> errors
     */
    private function writeEnvLocal(
        array $resolvedByDefinition,
        string $projectRoot,
        SymfonyStyle $io,
    ): array {
        $sectionedEnvVars = [];

        foreach ($resolvedByDefinition as $data) {
            /** @var EnvVarDefinitionInterface $definition */
            $definition = $data['definition'];
            $values = $data['values'];

            $resolvedVars = $definition->resolveEnvVars($values);

            $sectionName = $definition->getSectionName();

            if (!isset($sectionedEnvVars[$sectionName])) {
                $sectionedEnvVars[$sectionName] = [];
            }

            $sectionedEnvVars[$sectionName] = array_merge(
                $sectionedEnvVars[$sectionName],
                $resolvedVars,
            );
        }

        $envWriter = $this->createEnvWriter($projectRoot . '/.env.local');
        $warnings = $envWriter->write($sectionedEnvVars);

        foreach ($warnings as $warning) {
            $io->warning($warning);
        }

        return [];
    }

    /**
     * Execute a single installation step with checkpoint tracking.
     *
     * Returns null on success (or if the step was skipped), or an error string on failure.
     * Fatal steps should cause the caller to return early; non-fatal errors are collected.
     */
    private function executeStep(
        int $step,
        ?int $completedStep,
        InstallerCheckpoint $checkpoint,
        string $stepType,
        string $stepMessage,
        string $details,
        callable $action,
        bool $fatal = true,
    ): ?string {
        if (!$this->shouldRunStep($step, $completedStep)) {
            return null;
        }

        $this->dispatchStep($stepType, $stepMessage);

        try {
            $action();
            $checkpoint->markStepCompleted($step, $details);

            return null;
        } catch (Throwable $e) {
            $checkpoint->markStepFailed($step, $e->getMessage());
            $label = rtrim($stepMessage, '.');
            $this->logger->error($label . ' failed', ['exception' => $e]);

            return sprintf('%s failed: %s', $label, $e->getMessage());
        }
    }

    private function shouldRunStep(int $step, ?int $completedStep): bool
    {
        if ($completedStep === null) {
            return true;
        }

        return $step > $completedStep;
    }

    private function bootRealKernel(string $projectRoot): KernelInterface
    {
        // Phase 1 wrote .env.local after the process started, so the current
        // $_ENV / $_SERVER do not contain the new values (DATABASE_URL, etc.).
        // Re-load all .env files with override so the kernel can resolve them.
        $envFile = $projectRoot . '/.env';
        if (is_file($envFile)) {
            (new Dotenv())->bootEnv($envFile, overrideExistingVars: true);
        }

        $environment = Config::getEnvironment();

        $kernelClass = $_ENV['PIMCORE_KERNEL_CLASS'] ?? \App\Kernel::class;

        /** @var KernelInterface $kernel */
        $kernel = new $kernelClass($environment, true);

        Pimcore::setKernel($kernel);
        $kernel->boot();

        return $kernel;
    }

    private function getDatabaseConnection(KernelInterface $kernel): Connection
    {
        return $kernel->getContainer()->get('doctrine.dbal.default_connection');
    }

    /**
     * Create database schema from install.sql and set up infrastructure tables.
     * When no data source is present, also insert seed data (root nodes, system user, permissions).
     */
    private function setupDatabase(Connection $db, bool $hasDataSource): void
    {
        $this->databaseSetup->createSchema($db);

        if (!$hasDataSource) {
            $this->databaseSetup->insertSeedData($db);
        }
    }

    /**
     * @param array{username: string, password: string} $credentials
     */
    private function createOrUpdateAdminUser(Connection $db, array $credentials): void
    {
        $username = $credentials['username'];
        $password = $credentials['password'];

        $db->delete('users', ['name' => $username]);

        $db->insert('users', [
            'parentId' => 0,
            'name' => $username,
            'password' => Authentication::getPasswordHash($username, $password),
            'active' => 1,
            'admin' => 1,
            'type' => 'user',
            'language' => 'en',
        ]);
    }

    private function importDataSource(
        DataSourceInterface $dataSource,
        Connection $db,
        SymfonyStyle $io,
    ): void {
        if ($dataSource->isApplied($db)) {
            $io->text(sprintf(
                '  Data source "%s" already applied, skipping',
                $dataSource->getLabel(),
            ));

            return;
        }

        $dataSource->apply($db, $io);
        $io->text(sprintf('  <info>✓</info> %s', $dataSource->getLabel()));
    }

    private function installAssets(KernelInterface $kernel): void
    {
        $this->logger->info('Running assets:install command');

        $assetsInstaller = $kernel->getContainer()->get(AssetsInstaller::class);

        try {
            $assetsInstaller->install(['ansi' => false]);
        } catch (ProcessFailedException $e) {
            $this->logger->error('Assets installation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function clearKernelCacheDir(KernelInterface $kernel): void
    {
        $cacheDir = PIMCORE_SYMFONY_CACHE_DIRECTORY;

        if (!file_exists($cacheDir)) {
            return;
        }

        $oldCacheDir = substr($cacheDir, 0, -1) . '~';

        $filesystem = new Filesystem();
        if ($filesystem->exists($oldCacheDir)) {
            $filesystem->remove($oldCacheDir);
        }

        $filesystem->rename($cacheDir, $oldCacheDir);
        $filesystem->mkdir($cacheDir);

        try {
            $filesystem->remove($oldCacheDir);
        } catch (IOException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function cleanupNeedsInstallMarker(): void
    {
        try {
            $filesystem = new Filesystem();
            if ($filesystem->exists(self::NEEDS_INSTALL_MARKER)) {
                $filesystem->remove(self::NEEDS_INSTALL_MARKER);
            }
        } catch (IOException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function calculateTotalSteps(InstallProfileInterface $profile): void
    {
        // Base steps: boot, db, admin, register, reboot, install, assets, classes, migrations, finalize
        $this->totalSteps = 10;

        if ($profile->getDataSource() !== null) {
            $this->totalSteps++;
        }

        // Post-install commands, maintenance, and profile postInstall add 3 more potential steps
        $this->totalSteps += 3;
    }

    private function dispatchStep(string $type, string $message): void
    {
        $this->stepCounter++;

        $event = new InstallerStepEvent($type, $message, $this->stepCounter, $this->totalSteps);
        $this->eventDispatcher->dispatch($event, InstallEvents::EVENT_NAME_STEP);
    }
}
