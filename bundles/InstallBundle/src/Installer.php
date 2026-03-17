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
use Pimcore\Bundle\InstallBundle\BundleConfig\BundleWriter;
use Pimcore\Bundle\InstallBundle\Checkpoint\InstallerCheckpoint;
use Pimcore\Bundle\InstallBundle\Database\DatabaseSetup;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\Env\EnvWriter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Bundle\InstallBundle\Profile\DataSource\DataSourceInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommand;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommandsProviderInterface;
use Pimcore\Bundle\InstallBundle\Profile\PostInstallContext;
use Pimcore\Config;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tool\AssetsInstaller;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\Console;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
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
    private const string NEEDS_INSTALL_MARKER = PIMCORE_PRIVATE_VAR . '/config/needs-install.lock';

    /** Phase 2 step constants — used for checkpoint tracking */
    private const int STEP_SETUP_DATABASE = 12;

    private const int STEP_CREATE_ADMIN = 13;

    private const int STEP_IMPORT_DATA_SOURCE = 14;

    private const int STEP_REGISTER_BUNDLES = 15;

    private const int STEP_REBOOT_KERNEL = 16;

    private const int STEP_INSTALL_BUNDLES = 17;

    private const int STEP_INSTALL_ASSETS = 18;

    private const int STEP_REBUILD_CLASSES = 19;

    private const int STEP_MARK_MIGRATIONS = 20;

    private const int STEP_RUN_POST_INSTALL_COMMANDS = 21;

    private const int STEP_RUN_PROFILE_POST_INSTALL = 22;

    private const int STEP_FINALIZE = 23;

    private int $stepCounter = 0;

    private int $totalSteps = 0;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
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
        $activeDefinitions = $this->mergeDefinitions(
            $profile->getEnvVarDefinitions(),
            $extraDefinitions,
        );

        $categoryErrors = $this->validateDefinitionCategories($activeDefinitions);
        if ($categoryErrors !== []) {
            return $categoryErrors;
        }

        $skipErrors = $this->applySkipFlags($activeDefinitions, $skipKeys);
        if ($skipErrors !== []) {
            return $skipErrors;
        }

        $this->warnOnMissingBundles($profile, $io, $projectRoot);

        $bundleErrors = $this->validateProfileBundles($profile);
        if ($bundleErrors !== []) {
            return $bundleErrors;
        }

        $this->displayDefinitionSummary($activeDefinitions, $io);

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
        $checkpoint = new InstallerCheckpoint($projectRoot);
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
        $error = $this->executeStep(
            self::STEP_SETUP_DATABASE,
            $completedStep,
            $checkpoint,
            'setup_database',
            'Setting up database...',
            'Schema created',
            function () use ($kernel): void {
                $db = $this->getDatabaseConnection($kernel);
                $this->setupDatabase($db);
            },
        );
        if ($error !== null) {
            return [$error];
        }

        // Create admin user
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

        // Import data source
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

        // Register bundles
        $error = $this->executeStep(
            self::STEP_REGISTER_BUNDLES,
            $completedStep,
            $checkpoint,
            'register_bundles',
            'Registering bundles...',
            sprintf('%d bundles registered', count($profile->getBundles())),
            function () use ($profile): void {
                $this->registerBundles($profile);
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
                $this->clearKernelCacheDir($kernel);
                $kernel->shutdown();
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
                $this->installBundles($profile, $io);
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
                $this->rebuildClasses();
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
                $this->markMigrationsAsDone();
            },
            false,
        );
        if ($error !== null) {
            $errors[] = $error;
        }

        // Collect and run post-install commands
        $postInstallCommands = $this->collectPostInstallCommands(
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
                    $this->runPostInstallCommands($postInstallCommands, $io);
                },
            );
            if ($error !== null) {
                return array_merge($errors, [$error]);
            }
        }

        // Run profile postInstall()
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

        // Finalize
        $this->dispatchStep('finalize', 'Finalizing installation...');
        $this->clearKernelCacheDir($kernel);
        $this->cleanupNeedsInstallMarker();
        $checkpoint->remove();

        return $errors;
    }

    /**
     * Merge profile definitions with CLI-provided definitions.
     * CLI definitions override profile definitions on key collision.
     *
     * @param list<EnvVarDefinitionInterface> $profileDefs
     * @param list<EnvVarDefinitionInterface> $extraDefs
     *
     * @return array<string, EnvVarDefinitionInterface> key => definition
     */
    private function mergeDefinitions(array $profileDefs, array $extraDefs): array
    {
        $merged = [];

        foreach ($profileDefs as $def) {
            $merged[$def->getKey()] = $def;
        }

        foreach ($extraDefs as $def) {
            $merged[$def->getKey()] = $def;
        }

        return $merged;
    }

    /**
     * Apply --skip flags: remove definitions matching skipped keys.
     * Returns errors if trying to skip a required definition.
     *
     * @param array<string, EnvVarDefinitionInterface> $definitions modified in place
     * @param list<string> $skipKeys
     *
     * @return list<string> errors
     */
    private function applySkipFlags(array &$definitions, array $skipKeys): array
    {
        $errors = [];

        foreach ($skipKeys as $key) {
            if (!isset($definitions[$key])) {
                continue;
            }

            if ($definitions[$key]->isRequired()) {
                $errors[] = sprintf(
                    'Cannot skip required definition "%s" (%s)',
                    $key,
                    $definitions[$key]->getLabel(),
                );

                continue;
            }

            unset($definitions[$key]);
        }

        return $errors;
    }

    /**
     * Check installed Composer packages for Pimcore bundles not in the profile.
     * This is informational only — no error, just a warning.
     */
    private function warnOnMissingBundles(
        InstallProfileInterface $profile,
        SymfonyStyle $io,
        string $projectRoot,
    ): void {
        $installedJsonPath = $projectRoot . '/vendor/composer/installed.json';
        if (!file_exists($installedJsonPath)) {
            return;
        }

        $content = file_get_contents($installedJsonPath);
        if ($content === false) {
            return;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return;
        }

        // installed.json can be either {packages: [...]} or [...]
        $packages = $decoded['packages'] ?? $decoded;
        if (!is_array($packages)) {
            return;
        }

        $profileBundles = $profile->getBundles();
        $pimcoreBundlePackages = [];

        foreach ($packages as $package) {
            $name = $package['name'] ?? '';
            $type = $package['type'] ?? '';

            if ($type !== 'pimcore-bundle' || !is_string($name)) {
                continue;
            }

            $extra = $package['extra'] ?? [];
            $bundleClasses = $extra['pimcore']['bundles'] ?? [];
            if (!is_array($bundleClasses)) {
                continue;
            }

            foreach ($bundleClasses as $bundleClass) {
                if (!in_array($bundleClass, $profileBundles, true)) {
                    $pimcoreBundlePackages[$name] = $bundleClass;
                }
            }
        }

        if ($pimcoreBundlePackages !== []) {
            $io->warning(sprintf(
                'The following Pimcore bundles are installed via Composer but not '
                . "included in this profile:\n  %s",
                implode("\n  ", array_map(
                    static fn (string $pkg, string $cls) => sprintf('%s (%s)', $pkg, $cls),
                    array_keys($pimcoreBundlePackages),
                    array_values($pimcoreBundlePackages),
                )),
            ));
        }
    }

    /**
     * Validate that all bundle FQCNs from the profile are loadable.
     *
     * @return list<string> errors
     */
    private function validateProfileBundles(InstallProfileInterface $profile): array
    {
        $errors = [];

        foreach ($profile->getBundles() as $bundleFqcn) {
            if (!class_exists($bundleFqcn)) {
                $errors[] = sprintf(
                    'Bundle class "%s" from profile "%s" does not exist. '
                    . 'Is the package installed via Composer?',
                    $bundleFqcn,
                    $profile->getName(),
                );
            }
        }

        return $errors;
    }

    /**
     * Validate that the profile contains exactly one implementation of each
     * required definition category (search engine, messenger transport).
     *
     * @param array<string, EnvVarDefinitionInterface> $definitions
     *
     * @return list<string> errors
     */
    private function validateDefinitionCategories(array $definitions): array
    {
        $errors = [];

        $searchEngineCount = 0;
        $messengerTransportCount = 0;

        foreach ($definitions as $definition) {
            if ($definition instanceof SearchEngineDefinitionInterface) {
                $searchEngineCount++;
            }
            if ($definition instanceof MessengerTransportDefinitionInterface) {
                $messengerTransportCount++;
            }
        }

        if ($searchEngineCount === 0) {
            $errors[] = 'Profile must include exactly one SearchEngineDefinitionInterface '
                . 'implementation (e.g., OpenSearchEnvVarDefinition or ElasticsearchEnvVarDefinition).';
        } elseif ($searchEngineCount > 1) {
            $errors[] = sprintf(
                'Profile must include exactly one SearchEngineDefinitionInterface '
                . 'implementation, but found %d.',
                $searchEngineCount,
            );
        }

        if ($messengerTransportCount === 0) {
            $errors[] = 'Profile must include exactly one MessengerTransportDefinitionInterface '
                . 'implementation (e.g., DoctrineMessengerEnvVarDefinition or '
                . 'RabbitMqMessengerEnvVarDefinition).';
        } elseif ($messengerTransportCount > 1) {
            $errors[] = sprintf(
                'Profile must include exactly one MessengerTransportDefinitionInterface '
                . 'implementation, but found %d.',
                $messengerTransportCount,
            );
        }

        return $errors;
    }

    /**
     * @param array<string, EnvVarDefinitionInterface> $definitions
     */
    private function displayDefinitionSummary(array $definitions, SymfonyStyle $io): void
    {
        $io->text('<info>Definitions:</info>');

        foreach ($definitions as $definition) {
            $marker = $definition->isRequired() ? '●' : '○';
            $label = $definition->isRequired() ? 'required' : 'optional';
            $io->text(sprintf('  %s %-20s %s', $marker, $definition->getKey(), $label));
        }

        $io->newLine();
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

                $validationErrors = $this->shouldSkipValidation($definition, $collectedValues)
                    ? []
                    : $definition->validate($collectedValues);

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
     * Determine whether to skip validate() for a definition whose final env vars
     * were already present in the environment.
     *
     * When the ParameterCollector detects that all final (resolved) env vars for a
     * definition are already set, it skips transient parameter prompts and populates
     * collectedValues with the final DSN values directly. In this case, the
     * definition's validate() method — which expects transient parameter keys like
     * DATABASE_HOST, DATABASE_PORT, etc. — would fail because those keys are absent.
     *
     * We detect this situation by checking whether the collected values contain
     * any transient parameters. If a definition has transient params but none appear
     * in the collected values, the collector skipped them because the final env vars
     * were pre-set — and we trust the pre-set values.
     *
     * @param array<string, string> $collectedValues
     */
    private function shouldSkipValidation(EnvVarDefinitionInterface $definition, array $collectedValues): bool
    {
        $transientNames = [];
        foreach ($definition->getParameters() as $parameter) {
            if ($parameter->isTransient()) {
                $transientNames[] = $parameter->getEnvVarName();
            }
        }

        if ($transientNames === []) {
            return false;
        }

        foreach ($transientNames as $name) {
            if (isset($collectedValues[$name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract the final (resolved) env var values from collected values.
     *
     * When the ParameterCollector skipped transient params because all final
     * env vars were already present, the collected values contain the final
     * DSN values directly. This method extracts only those values that
     * correspond to the definition's resolved env var names, which is what
     * resolveEnvVars() would normally produce.
     *
     * @param array<string, string> $collectedValues
     *
     * @return array<string, string>
     */
    private function extractFinalEnvVars(EnvVarDefinitionInterface $definition, array $collectedValues): array
    {
        $dummyValues = [];
        foreach ($definition->getParameters() as $param) {
            $dummyValues[$param->getEnvVarName()] = $param->getDefaultValue() ?? '';
        }

        $resolvedNames = array_keys($definition->resolveEnvVars($dummyValues));
        $result = [];

        foreach ($resolvedNames as $name) {
            if (isset($collectedValues[$name])) {
                $result[$name] = $collectedValues[$name];
            }
        }

        return $result;
    }

    /**
     * @param array{username: string, password: string} $credentials
     *
     * @return list<string> errors
     */
    private function validateAdminCredentials(array $credentials): array
    {
        $errors = [];

        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';

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

            $resolvedVars = $this->shouldSkipValidation($definition, $values)
                ? $this->extractFinalEnvVars($definition, $values)
                : $definition->resolveEnvVars($values);

            $sectionName = $definition->getSectionName();

            if (!isset($sectionedEnvVars[$sectionName])) {
                $sectionedEnvVars[$sectionName] = [];
            }

            $sectionedEnvVars[$sectionName] = array_merge(
                $sectionedEnvVars[$sectionName],
                $resolvedVars,
            );
        }

        $envWriter = new EnvWriter($projectRoot . '/.env.local');
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
     */
    private function setupDatabase(Connection $db): void
    {
        (new DatabaseSetup())->createSchema($db);
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

    private function registerBundles(InstallProfileInterface $profile): void
    {
        $bundles = $profile->getBundles();

        if ($bundles === []) {
            return;
        }

        $writer = new BundleWriter();
        $writer->addBundlesToConfig($bundles, $bundles);
    }

    private function installBundles(InstallProfileInterface $profile, SymfonyStyle $io): void
    {
        $bundles = $profile->getBundles();
        $total = count($bundles);

        foreach ($bundles as $index => $bundleFqcn) {
            if ($this->isBundleInstalled($bundleFqcn)) {
                $io->text(sprintf(
                    '  [%d/%d] %s ... already installed',
                    $index + 1,
                    $total,
                    $this->getShortBundleName($bundleFqcn),
                ));

                continue;
            }

            $io->text(sprintf(
                '  [%d/%d] %s ...',
                $index + 1,
                $total,
                $this->getShortBundleName($bundleFqcn),
            ));

            $this->runCommand(
                ['pimcore:bundle:install', $bundleFqcn],
                'Installing ' . $this->getShortBundleName($bundleFqcn),
                $io,
            );
        }
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

    private function rebuildClasses(): void
    {
        $this->runCommand(
            ['pimcore:deployment:classes-rebuild', '-c'],
            'Rebuilding class definitions',
        );
    }

    private function markMigrationsAsDone(): void
    {
        $this->runCommand(
            ['doctrine:migrations:sync-metadata-storage', '-q'],
            'Sync migrations metadata storage',
        );

        $this->runCommand(
            [
                'doctrine:migrations:version',
                '--all', '--add', '--prefix=Pimcore\\Bundle\\CoreBundle', '-n', '-q',
            ],
            'Marking all migrations as done',
        );
    }

    /**
     * Collect post-install commands from profile, CLI providers, and bundle installers.
     * Deduplicate by command name (first wins), then sort by priority descending.
     *
     * @param list<PostInstallCommandsProviderInterface> $cliProviders
     *
     * @return list<PostInstallCommand>
     */
    private function collectPostInstallCommands(
        InstallProfileInterface $profile,
        array $cliProviders,
        KernelInterface $kernel,
    ): array {
        $commands = [];

        foreach ($profile->getPostInstallCommands() as $cmd) {
            $commands[$cmd->getCommand()] = $cmd;
        }

        foreach ($profile->getBundles() as $bundleFqcn) {
            try {
                $bundle = $kernel->getBundle($this->getShortBundleName($bundleFqcn));
                if (method_exists($bundle, 'getInstaller')) {
                    $installer = $bundle->getInstaller();
                    if ($installer instanceof PostInstallCommandsProviderInterface) {
                        foreach ($installer->getPostInstallCommands() as $cmd) {
                            if (!isset($commands[$cmd->getCommand()])) {
                                $commands[$cmd->getCommand()] = $cmd;
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                $this->logger->warning(
                    'Could not collect post-install commands from bundle {bundle}: {error}',
                    ['bundle' => $bundleFqcn, 'error' => $e->getMessage()],
                );
            }
        }

        foreach ($cliProviders as $provider) {
            foreach ($provider->getPostInstallCommands() as $cmd) {
                if (!isset($commands[$cmd->getCommand()])) {
                    $commands[$cmd->getCommand()] = $cmd;
                }
            }
        }

        $sorted = array_values($commands);
        usort(
            $sorted,
            static fn (PostInstallCommand $a, PostInstallCommand $b) => $b->getPriority() <=> $a->getPriority(),
        );

        return $sorted;
    }

    /**
     * @param list<PostInstallCommand> $commands
     */
    private function runPostInstallCommands(array $commands, SymfonyStyle $io): void
    {
        $total = count($commands);

        foreach ($commands as $index => $command) {
            $io->text(sprintf(
                '  [%d/%d] %s ...',
                $index + 1,
                $total,
                $command->getLabel(),
            ));

            $args = explode(' ', $command->getCommand());
            $this->runCommand($args, $command->getLabel(), $io);
        }
    }

    private function runCommand(
        array $arguments,
        string $taskName,
        ?SymfonyStyle $io = null,
    ): void {
        try {
            array_splice($arguments, 0, 0, [
                Console::getPhpCli(),
                PIMCORE_PROJECT_ROOT . '/bin/console',
            ]);

            $this->logger->info('Running {command} command', [
                'command' => implode(' ', $arguments),
            ]);

            $process = new Process($arguments);
            $process->setTimeout(0);
            $process->setWorkingDirectory(PIMCORE_PROJECT_ROOT);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            if ($io !== null) {
                $output = $process->getOutput();
                if ($output !== '') {
                    $io->writeln($output);
                }
            }
        } catch (ProcessFailedException $e) {
            $this->logger->error($e->getMessage());

            if ($io === null) {
                throw $e;
            }

            $process = $e->getProcess();
            $errorOutput = trim($process->getErrorOutput());

            if ($errorOutput !== '') {
                $io->getErrorStyle()->write($errorOutput);
            }

            $io->getErrorStyle()->note(
                $taskName . ' failed. Please run the following command manually:',
            );
            $io->getErrorStyle()->writeln(
                '  ' . str_replace(
                    ["'", '\\'],
                    ['', '\\\\'],
                    $process->getCommandLine(),
                ),
            );
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

    private function isBundleInstalled(string $bundleFqcn): bool
    {
        $shortName = $this->getShortBundleName($bundleFqcn);

        return SettingsStore::get('BUNDLE_INSTALLED__' . $shortName, 'pimcore') !== null;
    }

    /**
     * Extract short bundle name from FQCN.
     * e.g. "Pimcore\Bundle\SeoBundle\PimcoreSeoBundle" → "PimcoreSeoBundle"
     */
    private function getShortBundleName(string $bundleFqcn): string
    {
        $parts = explode('\\', $bundleFqcn);

        return end($parts);
    }

    private function calculateTotalSteps(InstallProfileInterface $profile): void
    {
        // Base steps: boot, db, admin, register, reboot, install, assets, classes, migrations, finalize
        $this->totalSteps = 10;

        if ($profile->getDataSource() !== null) {
            $this->totalSteps++;
        }

        // Post-install commands and profile postInstall add 2 more potential steps
        $this->totalSteps += 2;
    }

    private function dispatchStep(string $type, string $message): void
    {
        $this->stepCounter++;

        $event = new InstallerStepEvent($type, $message, $this->stepCounter, $this->totalSteps);
        $this->eventDispatcher->dispatch($event, InstallEvents::EVENT_NAME_STEP);
    }
}
