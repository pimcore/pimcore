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
use Pimcore\Bundle\InstallBundle\Console\ConsoleCommandRunner;
use Pimcore\Bundle\InstallBundle\Database\DatabaseSetup;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\Env\EnvWriter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ResolvedDefinition;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Bundle\InstallBundle\Profile\DataSource\DataSourceInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallStep;
use Pimcore\Bundle\InstallBundle\Profile\InstallStepFilterInterface;
use Pimcore\Bundle\InstallBundle\PostInstall\PostInstallRunner;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommandsProviderInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallHookInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallContext;
use Pimcore\Config;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Profile-first installer orchestrator.
 *
 * Phase 1 (InstallerKernel): collect parameters, validate connections, write .env.local
 * Phase 2 (real kernel): database setup, bundle install, post-install commands
 *
 * @internal
 */
final class Installer
{
    public const string NEEDS_INSTALL_MARKER = PIMCORE_PRIVATE_VAR . '/config/needs-install.lock';

    private int $stepCounter = 0;

    private int $totalSteps = 0;

    private ?KernelInterface $lastBootedKernel = null;

    /** @var InstallStep[] */
    private array $skippedSteps = [];

    /**
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
        private readonly ?\Closure $envWriterFactory = null,
    ) {
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
     * @param list<string|null> $skipValidation Skip validation flags from CLI
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
        array $skipValidation,
        array $adminCredentials,
        ParameterCollector $parameterCollector,
        SymfonyStyle $io,
        bool $interactive,
        string $projectRoot,
    ): array {
        $this->resolveSkippedSteps($profile);

        $result = ['resolved' => [], 'errors' => []];

        if ($this->isStepSkipped(InstallStep::CollectAndValidate)) {
            $this->skipStep(InstallStep::CollectAndValidate, 'Collecting and validating configuration...');
        } else {
            $activeDefinitions = $this->definitionResolver->mergeDefinitions(
                $profile->getEnvVarDefinitions(),
                $extraDefinitions,
            );

            $categoryErrors = $this->definitionResolver->validateDefinitionCategories($activeDefinitions);
            if ($categoryErrors !== []) {
                return $categoryErrors;
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
                $skipValidation,
            );

            if ($result['errors'] !== []) {
                return $result['errors'];
            }

            $credentialErrors = $this->databaseSetup->validateAdminCredentials($adminCredentials);
            if ($credentialErrors !== []) {
                return $credentialErrors;
            }
        }

        if ($this->isStepSkipped(InstallStep::WriteEnv)) {
            $this->skipStep(InstallStep::WriteEnv, 'Writing .env.local...');
        } else {
            $this->dispatchStep(InstallStep::WriteEnv, 'Writing .env.local...');
            $writeErrors = $this->writeEnvLocal($result['resolved'], $projectRoot, $io);

            if ($writeErrors !== []) {
                return $writeErrors;
            }

            $io->text('  <info>✓</info> .env.local written');
        }

        if ($this->isStepSkipped(InstallStep::WriteDoctrineConfig)) {
            $this->skipStep(InstallStep::WriteDoctrineConfig, 'Writing Doctrine mapping types config...');
        } else {
            $this->dispatchStep(InstallStep::WriteDoctrineConfig, 'Writing Doctrine mapping types config...');
            $this->writeDoctrineConfig($projectRoot);
            $io->text('  <info>✓</info> Doctrine mapping types config written');
        }

        return [];
    }

    /**
     * Run Phase 2: database setup, bundle install, post-install commands.
     *
     * Boots the real kernel, performs all installation steps.
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
        $this->calculateTotalSteps($profile);
        $errors = [];

        $this->dispatchStep(InstallStep::BootKernel, 'Booting application kernel...');
        $kernel = $this->bootRealKernel($projectRoot);

        $error = $this->executeStepSetupDatabase($kernel, $profile);
        if ($error !== null) {
            return [$error];
        }

        $error = $this->executeStepImportDataSource($kernel, $profile, $io);
        if ($error !== null) {
            return [$error];
        }

        $error = $this->executeStepCreateAdmin($kernel, $adminCredentials);
        if ($error !== null) {
            return [$error];
        }

        $error = $this->executeStepRegisterBundles($profile);
        if ($error !== null) {
            return [$error];
        }

        $error = $this->executeStepRebootKernel($kernel, $projectRoot);
        if ($error !== null) {
            return [$error];
        }
        // Kernel reference updated after reboot
        $kernel = $this->lastBootedKernel;

        $error = $this->executeStepInstallBundles($profile, $io);
        if ($error !== null) {
            return [$error];
        }

        $error = $this->executeStepInstallAssets();
        if ($error !== null) {
            return [$error];
        }

        $error = $this->executeStepRebuildClasses();
        if ($error !== null) {
            $errors[] = $error;
        }

        $error = $this->executeStepMarkMigrations();
        if ($error !== null) {
            $errors[] = $error;
        }

        $error = $this->executeStepPostInstallCommands(
            $profile,
            $cliPostInstallProviders,
            $kernel,
            $io,
        );
        if ($error !== null) {
            return array_merge($errors, [$error]);
        }

        $error = $this->executeStepRunMaintenance();
        if ($error !== null) {
            $errors[] = $error;
        }

        $error = $this->executeStepProfilePostInstall($profile, $kernel, $io);
        if ($error !== null) {
            return array_merge($errors, [$error]);
        }

        $this->dispatchStep(InstallStep::Finalize, 'Finalizing installation...');
        $this->clearKernelCacheDir($kernel);
        $this->cleanupNeedsInstallMarker();

        return $errors;
    }

    /**
     * Collect parameters for each definition and validate them, with retry support
     * for interactive mode.
     *
     * @param array<string, EnvVarDefinitionInterface> $activeDefinitions
     * @param list<string|null> $skipValidation Skip validation flags from CLI
     *
     * @return array{
     *     resolved: array<string, ResolvedDefinition>,
     *     errors: list<string>,
     * }
     */
    private function collectAndValidateDefinitions(
        array $activeDefinitions,
        ParameterCollector $parameterCollector,
        SymfonyStyle $io,
        bool $interactive,
        array $skipValidation,
    ): array {
        $resolvedByDefinition = [];
        $errors = [];
        $this->dispatchStep(InstallStep::CollectAndValidate, 'Collecting and validating configuration...');

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

                if ($this->definitionResolver->shouldSkipValidation($definition, $skipValidation)) {
                    $io->text('  <comment>!</comment> Validation skipped');
                    $resolvedByDefinition[$key] = new ResolvedDefinition($definition, $collectedValues);
                    break;
                }

                $validationErrors = $definition->validate($collectedValues);

                if ($validationErrors === []) {
                    $io->text('  <info>✓</info> Validation successful');
                    $resolvedByDefinition[$key] = new ResolvedDefinition($definition, $collectedValues);
                    break;
                }

                foreach ($validationErrors as $error) {
                    $io->error(sprintf('%s: %s', $definition->getLabel(), $error));
                }

                $shouldRetry = $interactive && $attempt < $maxRetries && $io->confirm('Retry?', true);

                if (!$shouldRetry) {
                    $errors = array_merge(
                        $errors,
                        $this->formatDefinitionErrors($definition, $validationErrors),
                    );
                    break;
                }
            }
        }

        return ['resolved' => $resolvedByDefinition, 'errors' => $errors];
    }

    /**
     * @param list<string> $validationErrors
     *
     * @return list<string>
     */
    private function formatDefinitionErrors(
        EnvVarDefinitionInterface $definition,
        array $validationErrors,
    ): array {
        return array_map(
            static fn (string $err) => sprintf('%s: %s', $definition->getLabel(), $err),
            $validationErrors,
        );
    }

    private function getDatabaseConnection(KernelInterface $kernel): Connection
    {
        return $kernel->getContainer()->get('doctrine.dbal.default_connection');
    }

    /**
     * Resolve env vars from each definition and write to .env.local.
     *
     * @param array<string, ResolvedDefinition> $resolvedByDefinition
     *
     * @return list<string> errors
     */
    private function writeEnvLocal(
        array $resolvedByDefinition,
        string $projectRoot,
        SymfonyStyle $io,
    ): array {
        $sectionedEnvVars = [];

        foreach ($resolvedByDefinition as $resolved) {
            $definition = $resolved->getDefinition();
            $resolvedVars = $definition->resolveEnvVars($resolved->getValues());

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
     * Write static Doctrine mapping types config.
     *
     * Registers database type mappings (e.g. BIT -> boolean)
     * that Doctrine DBAL does not support natively. Without this config,
     * introspecting a schema containing these column types throws
     * "Unknown database type" errors.
     *
     * This config was historically part of Pimcore's default.yaml but was
     * moved to installer-generated config in PR #7848 to avoid container
     * build failures when no database connection is configured yet.
     */
    private function writeDoctrineConfig(string $projectRoot): void
    {
        $configDir = $projectRoot . '/config/packages';
        $filesystem = new Filesystem();

        if (!$filesystem->exists($configDir)) {
            $filesystem->mkdir($configDir);
        }

        $content = <<<'YAML'
doctrine:
    dbal:
        connections:
            default:
                mapping_types:
                    bit: boolean

YAML;

        $filesystem->dumpFile($configDir . '/doctrine_mapping_types.yaml', $content);
    }

    /**
     * Execute a single installation step.
     *
     * Returns null on success, or an error string on failure.
     * Fatal steps should cause the caller to return early; non-fatal errors are collected.
     */
    private function executeStep(
        InstallStep $step,
        string $stepMessage,
        string $details,
        callable $action,
        bool $fatal = true,
    ): ?string {
        $this->dispatchStep($step, $stepMessage);

        try {
            $action();
            $this->logger->info($details);

            return null;
        } catch (Throwable $e) {
            $label = rtrim($stepMessage, '.');
            $this->logger->error($label . ' failed', ['exception' => $e]);

            return sprintf('%s failed: %s', $label, $e->getMessage());
        }
    }

    private function executeStepSetupDatabase(
        KernelInterface $kernel,
        InstallProfileInterface $profile,
    ): ?string {
        $hasDataSource = $profile->getDataSource() !== null;

        return $this->executeStep(
            InstallStep::SetupDatabase,
            'Setting up database...',
            'Schema created',
            function () use ($kernel, $hasDataSource): void {
                $db = $this->getDatabaseConnection($kernel);
                $this->databaseSetup->createSchema($db);

                if (!$hasDataSource) {
                    $this->databaseSetup->insertSeedData($db);
                }
            },
        );
    }

    private function executeStepImportDataSource(
        KernelInterface $kernel,
        InstallProfileInterface $profile,
        SymfonyStyle $io,
    ): ?string {
        $dataSource = $profile->getDataSource();
        if ($dataSource === null) {
            return null;
        }

        return $this->executeStep(
            InstallStep::ImportData,
            'Importing data...',
            $dataSource->getLabel(),
            function () use ($dataSource, $kernel, $io): void {
                $db = $this->getDatabaseConnection($kernel);
                $this->importDataSource($dataSource, $db, $io);
            },
        );
    }

    /**
     * @param array{username: string, password: string} $credentials
     */
    private function executeStepCreateAdmin(
        KernelInterface $kernel,
        array $credentials,
    ): ?string {
        return $this->executeStep(
            InstallStep::CreateAdmin,
            'Creating admin user...',
            'Admin user created',
            function () use ($kernel, $credentials): void {
                $db = $this->getDatabaseConnection($kernel);
                $this->databaseSetup->createOrUpdateAdminUser($db, $credentials);
            },
        );
    }

    private function executeStepRegisterBundles(InstallProfileInterface $profile): ?string
    {
        return $this->executeStep(
            InstallStep::RegisterBundles,
            'Registering bundles...',
            sprintf('%d bundles registered', count($profile->getBundles())),
            function () use ($profile): void {
                $this->bundleInstaller->registerBundles($profile);
            },
        );
    }

    private function executeStepRebootKernel(
        KernelInterface $kernel,
        string $projectRoot,
    ): ?string {
        return $this->executeStep(
            InstallStep::RebootKernel,
            'Rebooting kernel...',
            'Kernel rebooted',
            function () use ($kernel, $projectRoot): void {
                $kernel->shutdown();
                $this->clearKernelCacheDir($kernel);
                $this->lastBootedKernel = $this->bootRealKernel($projectRoot);
            },
        );
    }

    private function executeStepInstallBundles(
        InstallProfileInterface $profile,
        SymfonyStyle $io,
    ): ?string {
        return $this->executeStep(
            InstallStep::InstallBundles,
            'Installing bundles...',
            'Bundles installed',
            function () use ($profile, $io): void {
                $this->bundleInstaller->installBundles($profile, $io, skipPostChangeCommands: true);
            },
        );
    }

    private function executeStepInstallAssets(): ?string
    {
        return $this->executeStep(
            InstallStep::InstallAssets,
            'Installing assets...',
            'Assets installed',
            function (): void {
                $this->commandRunner->installAssets();
            },
        );
    }

    private function executeStepRebuildClasses(): ?string
    {
        return $this->executeStep(
            InstallStep::RebuildClasses,
            'Rebuilding class definitions...',
            'Classes rebuilt',
            function (): void {
                $this->commandRunner->rebuildClasses();
            },
            false,
        );
    }

    private function executeStepMarkMigrations(): ?string
    {
        return $this->executeStep(
            InstallStep::MarkMigrations,
            'Marking migrations as done...',
            'Migrations marked',
            function (): void {
                $this->commandRunner->markMigrationsAsDone();
            },
            false,
        );
    }

    /**
     * @param list<PostInstallCommandsProviderInterface> $cliPostInstallProviders
     */
    private function executeStepPostInstallCommands(
        InstallProfileInterface $profile,
        array $cliPostInstallProviders,
        KernelInterface $kernel,
        SymfonyStyle $io,
    ): ?string {
        $postInstallCommands = $this->postInstallRunner->collectPostInstallCommands(
            $profile,
            $cliPostInstallProviders,
            $kernel,
        );

        if ($postInstallCommands === []) {
            return null;
        }

        return $this->executeStep(
            InstallStep::PostInstallCommands,
            'Running post-install commands...',
            sprintf('%d commands executed', count($postInstallCommands)),
            function () use ($postInstallCommands, $io): void {
                $this->postInstallRunner->runPostInstallCommands(
                    $postInstallCommands,
                    $io,
                );
            },
        );
    }

    private function executeStepRunMaintenance(): ?string
    {
        return $this->executeStep(
            InstallStep::RunMaintenance,
            'Running maintenance...',
            'Maintenance completed',
            function (): void {
                $this->commandRunner->runMaintenance();
            },
            false,
        );
    }

    private function executeStepProfilePostInstall(
        InstallProfileInterface $profile,
        KernelInterface $kernel,
        SymfonyStyle $io,
    ): ?string {
        if (!$profile instanceof PostInstallHookInterface) {
            return null;
        }

        return $this->executeStep(
            InstallStep::ProfilePostInstall,
            'Running profile post-install...',
            'Profile postInstall() completed',
            function () use ($kernel, $profile, $io): void {
                $db = $this->getDatabaseConnection($kernel);
                $context = new PostInstallContext($db, $io);
                $profile->postInstall($context);
            },
        );
    }

    private function resolveSkippedSteps(InstallProfileInterface $profile): void
    {
        $this->skippedSteps = $profile instanceof InstallStepFilterInterface
            ? $profile->getSkippedInstallSteps()
            : [];
    }

    private function isStepSkipped(InstallStep $step): bool
    {
        return in_array($step, $this->skippedSteps, true);
    }

    private function skipStep(InstallStep $step, string $message): void
    {
        $this->logger->info(sprintf('Skipping step "%s": %s', $step->value, $message));
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

        $this->lastBootedKernel = $kernel;

        return $kernel;
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
        $steps = 0;

        $steps++; // boot_kernel
        $steps++; // setup_database

        if ($profile->getDataSource() !== null) {
            $steps++; // import_data
        }

        $steps++; // create_admin
        $steps++; // register_bundles
        $steps++; // reboot_kernel
        $steps++; // install_bundles
        $steps++; // install_assets
        $steps++; // rebuild_classes
        $steps++; // mark_migrations
        $steps++; // post_install_commands
        $steps++; // run_maintenance
        $steps++; // profile_post_install
        $steps++; // finalize

        $this->totalSteps = $steps;
    }

    private function dispatchStep(InstallStep $step, string $message): void
    {
        $this->stepCounter++;

        $event = new InstallerStepEvent($step, $message, $this->stepCounter, $this->totalSteps);
        $this->eventDispatcher->dispatch($event, InstallEvents::EVENT_NAME_STEP);
    }
}
