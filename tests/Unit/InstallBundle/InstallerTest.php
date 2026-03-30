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

namespace Pimcore\Tests\Unit\InstallBundle;

use Pimcore\Bundle\InstallBundle\Collector\ArrayEnvVarReader;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Bundle\InstallBundle\Profile\DataSource\DataSourceInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallStep;
use Pimcore\Bundle\InstallBundle\Profile\InstallStepFilterInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Unit\InstallBundle\Support\InstallBundleTestHelperTrait;
use Pimcore\Tests\Unit\InstallBundle\Support\NoopMessengerTransportDefinition;
use Pimcore\Tests\Unit\InstallBundle\Support\NoopSearchEngineDefinition;
use Pimcore\Tests\Unit\InstallBundle\Support\NoopSearchEngineDefinitionThatFails;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Integration tests for the Installer's Phase 1 (runPhaseOne).
 *
 * Phase 2 (runPhaseTwo) requires a running database and real kernel, so it
 * is tested manually or via a full Docker-based integration test.
 *
 * @internal
 */
final class InstallerTest extends TestCase
{
    use InstallBundleTestHelperTrait;

    private string $tempDir;

    private Installer $installer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/pimcore_installer_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->installer = $this->createInstaller();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testDefinitionMergeCliOverridesProfileOnKeyCollision(): void
    {
        $profileDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'profile-host')],
            ['DB_HOST'],
        );
        $cliDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'cli-host')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$profileDef]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [$cliDef],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        // Verify CLI definition won (its default value was written)
        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('cli-host', $envContent);
        $this->assertStringNotContainsString('profile-host', $envContent);
    }

    public function testDefinitionMergeCombinesUniqueKeys(): void
    {
        $profileDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );
        $cliDef = $this->createMockDefinition(
            'redis',
            false,
            [new ConfigParameter('REDIS_URL', 'Redis URL', ParameterType::Url, defaultValue: 'redis://localhost:6379')],
            ['REDIS_URL'],
        );

        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('REDIS_URL', 'redis://localhost:6379');

        $profile = $this->createMockProfile([$profileDef]);
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [$cliDef],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        // Both definitions should produce env vars
        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="localhost"', $envContent);
        $this->assertStringContainsString('REDIS_URL="redis://localhost:6379"', $envContent);
    }

    public function testAdminUsernameTooShortReturnsError(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'ab', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('username must be at least 4', $errors[0]);
    }

    public function testAdminPasswordTooShortReturnsError(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'ab'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('password must be at least 4', $errors[0]);
    }

    public function testValidationErrorsAreAggregated(): void
    {
        $failingDef = $this->createFailingDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['Connection refused', 'DNS lookup failed'],
        );

        $profile = $this->createMockProfile([$failingDef]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('Connection refused', $errors[0]);
        $this->assertStringContainsString('DNS lookup failed', $errors[1]);
    }

    public function testValidationNotCalledForSkippedOptionalDefinition(): void
    {
        $tracker = new \ArrayObject(['count' => 0]);

        $optionalDef = new class($tracker) implements EnvVarDefinitionInterface {
            public function __construct(private readonly \ArrayObject $tracker)
            {
            }

            public function getKey(): string
            {
                return 'redis';
            }

            public function getLabel(): string
            {
                return 'Redis';
            }

            public function isRequired(): bool
            {
                return false;
            }

            public function getSectionName(): string
            {
                return 'test';
            }

            public function getParameters(): array
            {
                return [new ConfigParameter('REDIS_URL', 'Redis URL', ParameterType::Url)];
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                return ['REDIS_URL' => $collectedValues['REDIS_URL'] ?? ''];
            }

            public function validate(array $collectedValues): array
            {
                $this->tracker['count']++;

                return ['Should not be called'];
            }
        };

        // No env vars set + non-interactive → optional definition is skipped
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        // Need at least one required definition to avoid empty profile
        $requiredDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $errors = $this->installer->runPhaseOne(
            $this->createMockProfile([$requiredDef, $optionalDef]),
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $this->assertSame(
            0,
            $tracker['count'],
            'validate() should not be called for a skipped optional definition',
        );
    }

    public function testMultipleDefinitionsWithErrorsCollectsAll(): void
    {
        $failingDef1 = $this->createFailingDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['Database connection refused'],
        );
        $failingDef2 = $this->createFailingDefinition(
            'redis',
            true,
            [new ConfigParameter('REDIS_URL', 'Redis', ParameterType::Url, defaultValue: 'redis://localhost')],
            ['Redis unavailable'],
        );

        $profile = $this->createMockProfile([$failingDef1, $failingDef2]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        // Both definitions' errors are collected (formatted as "Label: error")
        $this->assertCount(2, $errors);
        $this->assertStringContainsString('Database connection refused', $errors[0]);
        $this->assertStringContainsString('Redis unavailable', $errors[1]);
    }

    public function testBothAdminCredentialsInvalidReportsBothErrors(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'ab', 'password' => 'cd'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        // Both credential errors should be reported
        $this->assertCount(2, $errors);
        $hasUsernameError = false;
        $hasPasswordError = false;
        foreach ($errors as $error) {
            if (str_contains($error, 'username')) {
                $hasUsernameError = true;
            }
            if (str_contains($error, 'password')) {
                $hasPasswordError = true;
            }
        }
        $this->assertTrue($hasUsernameError, 'Expected username validation error');
        $this->assertTrue($hasPasswordError, 'Expected password validation error');
    }

    public function testEmptyAdminUsernameReturnsError(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => '', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('username', $errors[0]);
    }

    public function testEmptyAdminPasswordReturnsError(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => ''],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('password', $errors[0]);
    }

    public function testCliDefinitionsMergedWithProfileDefinitions(): void
    {
        $profileDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );
        $cliDef = $this->createMockDefinition(
            'custom-service',
            true,
            [new ConfigParameter(
                'CUSTOM_URL',
                'Custom',
                ParameterType::Url,
                defaultValue: 'http://localhost:8080',
            )],
            ['CUSTOM_URL'],
        );

        $profile = $this->createMockProfile([$profileDef]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [$cliDef],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="localhost"', $envContent);
        $this->assertStringContainsString('CUSTOM_URL="http://localhost:8080"', $envContent);
    }

    public function testEnvVarTakesPrecedenceOverDefault(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'default-host')],
            ['DB_HOST'],
        );

        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('DB_HOST', 'env-host');

        $profile = $this->createMockProfile([$def]);
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="env-host"', $envContent);
        $this->assertStringNotContainsString('default-host', $envContent);
    }

    public function testFullPhaseOneWritesEnvLocalCorrectly(): void
    {
        $dbDef = $this->createMockDefinition(
            'database',
            true,
            [
                new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'db'),
                new ConfigParameter('DB_PORT', 'Port', ParameterType::Integer, defaultValue: '3306'),
                new ConfigParameter('DB_NAME', 'Name', ParameterType::String, defaultValue: 'pimcore'),
            ],
            ['DB_HOST', 'DB_PORT', 'DB_NAME'],
            'pimcore/pimcore',
        );

        $mercureDef = $this->createMockDefinition(
            'mercure',
            true,
            [new ConfigParameter('MERCURE_URL', 'URL', ParameterType::Url, defaultValue: 'http://localhost/hub')],
            ['MERCURE_URL'],
            'mercure',
        );

        $profile = $this->createMockProfile([$dbDef, $mercureDef]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'securepassword'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $this->assertFileExists($this->tempDir . '/.env.local');

        $envContent = file_get_contents($this->tempDir . '/.env.local');

        // Check section headers
        $this->assertStringContainsString('###> pimcore/pimcore ###', $envContent);
        $this->assertStringContainsString('###< pimcore/pimcore ###', $envContent);
        $this->assertStringContainsString('###> mercure ###', $envContent);
        $this->assertStringContainsString('###< mercure ###', $envContent);

        // Check values
        $this->assertStringContainsString('DB_HOST="db"', $envContent);
        $this->assertStringContainsString('DB_PORT="3306"', $envContent);
        $this->assertStringContainsString('DB_NAME="pimcore"', $envContent);
        $this->assertStringContainsString('MERCURE_URL="http://localhost/hub"', $envContent);
    }

    public function testPhaseOnePreservesExistingEnvLocalContent(): void
    {
        // Create a pre-existing .env.local with custom content
        file_put_contents(
            $this->tempDir . '/.env.local',
            "# Custom user config\nAPP_DEBUG=true\n\n"
            . "###> pimcore/pimcore ###\nOLD_VAR=old-value\n###< pimcore/pimcore ###\n",
        );

        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'new-host')],
            ['DB_HOST'],
            'pimcore/pimcore',
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        $envContent = file_get_contents($this->tempDir . '/.env.local');

        // User content preserved
        $this->assertStringContainsString('APP_DEBUG=true', $envContent);

        // Old section replaced with new content
        $this->assertStringNotContainsString('OLD_VAR=old-value', $envContent);
        $this->assertStringContainsString('DB_HOST="new-host"', $envContent);
    }

    public function testPhaseOneWithNoDefinitions(): void
    {
        // A profile with no definitions should still succeed (writes nothing to .env.local)
        $profile = $this->createMockProfile([]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
    }

    public function testInvalidBundleFqcnReturnsError(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        // Use a bundle FQCN that doesn't exist
        $profile = $this->createMockProfile(
            [$def],
            [],
            ['NonExistent\\Bundle\\SomeFakeBundle'],
        );

        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('does not exist', $errors[0]);
        $this->assertStringContainsString('NonExistent\\Bundle\\SomeFakeBundle', $errors[0]);
    }

    public function testSpecialCharactersInValuesAreEscaped(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter(
                'DB_PASSWORD',
                'Password',
                ParameterType::Secret,
                defaultValue: 'p@$$w0rd with spaces',
            )],
            ['DB_PASSWORD'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        $envContent = file_get_contents($this->tempDir . '/.env.local');
        // Value with special chars should be quoted
        $this->assertStringContainsString('DB_PASSWORD=', $envContent);
        // Ensure $ is escaped
        $this->assertStringContainsString('\\$', $envContent);
    }

    public function testMissingSearchEngineDefinitionReturnsError(): void
    {
        $messengerDef = $this->createNoopMessengerTransportDefinition();
        $dbDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$dbDef, $messengerDef], includeDefaultMarkerDefs: false);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('SearchEngineDefinitionInterface', $errors[0]);
    }

    public function testMissingMessengerTransportDefinitionReturnsError(): void
    {
        $searchDef = $this->createNoopSearchEngineDefinition();
        $dbDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$dbDef, $searchDef], includeDefaultMarkerDefs: false);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('MessengerTransportDefinitionInterface', $errors[0]);
    }

    public function testDuplicateSearchEngineDefinitionsReturnError(): void
    {
        $searchDef1 = $this->createNoopSearchEngineDefinition();

        // Create a second search engine def with a different key
        $searchDef2 = new class() implements SearchEngineDefinitionInterface {
            public function getKey(): string
            {
                return 'second-search-engine';
            }

            public function getLabel(): string
            {
                return 'Second Search Engine';
            }

            public function isRequired(): bool
            {
                return true;
            }

            public function getSectionName(): string
            {
                return 'test';
            }

            public function getParameters(): array
            {
                return [];
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                return ['SECOND_SEARCH_DSN' => 'search://localhost'];
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };

        $messengerDef = $this->createNoopMessengerTransportDefinition();

        $profile = $this->createMockProfile(
            [$searchDef1, $searchDef2, $messengerDef],
            includeDefaultMarkerDefs: false,
        );
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('exactly one', $errors[0]);
        $this->assertStringContainsString('found 2', $errors[0]);
    }

    public function testSkipValidationGlobalSkipsAllValidation(): void
    {
        $failingDef = $this->createFailingDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['Connection refused'],
        );

        $profile = $this->createMockProfile([$failingDef]);
        $collector = new ParameterCollector(new ArrayEnvVarReader());

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [null],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="localhost"', $envContent);
    }

    public function testSkipValidationByKeySkipsOnlyMatchingDefinition(): void
    {
        $failingDef = $this->createFailingDefinition(
            'redis',
            true,
            [new ConfigParameter('REDIS_URL', 'Redis', ParameterType::Url, defaultValue: 'redis://localhost')],
            ['Redis unavailable'],
        );
        $passingDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$passingDef, $failingDef]);
        $collector = new ParameterCollector(new ArrayEnvVarReader());

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            ['redis'],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="localhost"', $envContent);
        $this->assertStringContainsString('REDIS_URL="redis://localhost"', $envContent);
    }

    public function testSkipValidationByShortClassNameSkipsMatchingDefinition(): void
    {
        $failingDef = new NoopSearchEngineDefinitionThatFails();
        $passingDef = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );
        $messengerDef = $this->createNoopMessengerTransportDefinition();

        $profile = $this->createMockProfile(
            [$passingDef, $failingDef, $messengerDef],
            includeDefaultMarkerDefs: false,
        );
        $collector = new ParameterCollector(new ArrayEnvVarReader());

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            ['NoopSearchEngineDefinitionThatFails'],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="localhost"', $envContent);
    }

    public function testPhaseOneWritesDoctrineConfigFile(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        $configFile = $this->tempDir . '/config/packages/doctrine_mapping_types.yaml';
        $this->assertFileExists($configFile);

        $content = file_get_contents($configFile);

        // Verify the YAML structure contains the required mapping types
        $this->assertStringContainsString('doctrine:', $content);
        $this->assertStringContainsString('dbal:', $content);
        $this->assertStringContainsString('connections:', $content);
        $this->assertStringContainsString('default:', $content);
        $this->assertStringContainsString('mapping_types:', $content);
        $this->assertStringContainsString('bit: boolean', $content);
    }

    public function testPhaseOneCreatesConfigPackagesDirectoryIfMissing(): void
    {
        // Ensure config/packages does NOT exist before running
        $this->assertDirectoryDoesNotExist($this->tempDir . '/config/packages');

        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $this->assertDirectoryExists($this->tempDir . '/config/packages');
        $this->assertFileExists($this->tempDir . '/config/packages/doctrine_mapping_types.yaml');
    }

    public function testPhaseOneDoctrineConfigExactContent(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        $expectedContent = <<<'YAML'
doctrine:
    dbal:
        connections:
            default:
                mapping_types:
                    bit: boolean

YAML;

        $actualContent = file_get_contents(
            $this->tempDir . '/config/packages/doctrine_mapping_types.yaml',
        );
        $this->assertSame($expectedContent, $actualContent);
    }

    public function testSkipValidationNonMatchingKeyStillValidates(): void
    {
        $failingDef = $this->createFailingDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['Connection refused'],
        );

        $profile = $this->createMockProfile([$failingDef]);
        $collector = new ParameterCollector(new ArrayEnvVarReader());

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            ['redis'],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Connection refused', $errors[0]);
    }

    // -----------------------------------------------------------------------
    // Install step filtering tests
    // -----------------------------------------------------------------------

    public function testPhaseOneSkipWriteEnvDoesNotWriteEnvLocal(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfileWithSkippedSteps(
            [$def],
            [InstallStep::WriteEnv],
        );

        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $this->assertFileDoesNotExist($this->tempDir . '/.env.local');

        // Doctrine config should still be written
        $this->assertFileExists($this->tempDir . '/config/packages/doctrine_mapping_types.yaml');
    }

    public function testPhaseOneSkipWriteDoctrineConfigDoesNotWriteConfig(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfileWithSkippedSteps(
            [$def],
            [InstallStep::WriteDoctrineConfig],
        );

        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $this->assertFileDoesNotExist($this->tempDir . '/config/packages/doctrine_mapping_types.yaml');

        // .env.local should still be written
        $this->assertFileExists($this->tempDir . '/.env.local');
        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="localhost"', $envContent);
    }

    public function testPhaseOneSkipCollectAndValidateBypassesValidation(): void
    {
        $failingDef = $this->createFailingDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['Connection refused'],
        );

        $profile = $this->createMockProfileWithSkippedSteps(
            [$failingDef],
            [InstallStep::CollectAndValidate],
        );

        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        // No errors — validation was bypassed
        $this->assertSame([], $errors);
    }

    public function testPhaseOneSkipAllStepsWritesNothing(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfileWithSkippedSteps(
            [$def],
            [InstallStep::CollectAndValidate, InstallStep::WriteEnv, InstallStep::WriteDoctrineConfig],
        );

        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);
        $this->assertFileDoesNotExist($this->tempDir . '/.env.local');
        $this->assertFileDoesNotExist($this->tempDir . '/config/packages/doctrine_mapping_types.yaml');
    }

    public function testPhaseOneWithoutInstallStepFilterInterfaceRunsAllSteps(): void
    {
        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        // Use a normal profile without InstallStepFilterInterface
        $profile = $this->createMockProfile([$def]);

        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $errors = $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertSame([], $errors);

        // Both files should be written — all steps ran
        $this->assertFileExists($this->tempDir . '/.env.local');
        $envContent = file_get_contents($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DB_HOST="localhost"', $envContent);

        $this->assertFileExists($this->tempDir . '/config/packages/doctrine_mapping_types.yaml');
    }

    public function testPhaseOneEventsUseInstallStepEnum(): void
    {
        $eventDispatcher = new EventDispatcher();

        /** @var list<InstallerStepEvent> $receivedEvents */
        $receivedEvents = [];
        $eventDispatcher->addListener(
            InstallEvents::EVENT_NAME_STEP,
            static function (InstallerStepEvent $event) use (&$receivedEvents): void {
                $receivedEvents[] = $event;
            },
        );

        $installer = $this->createInstaller(eventDispatcher: $eventDispatcher);

        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfile([$def]);
        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $this->assertNotEmpty($receivedEvents);

        foreach ($receivedEvents as $event) {
            $this->assertInstanceOf(InstallStep::class, $event->getStep());
        }
    }

    public function testPhaseOneSkippedStepsDoNotProduceEvents(): void
    {
        $eventDispatcher = new EventDispatcher();

        /** @var list<InstallStep> $receivedSteps */
        $receivedSteps = [];
        $eventDispatcher->addListener(
            InstallEvents::EVENT_NAME_STEP,
            static function (InstallerStepEvent $event) use (&$receivedSteps): void {
                $receivedSteps[] = $event->getStep();
            },
        );

        $installer = $this->createInstaller(eventDispatcher: $eventDispatcher);

        $def = $this->createMockDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
            ['DB_HOST'],
        );

        $profile = $this->createMockProfileWithSkippedSteps(
            [$def],
            [InstallStep::WriteEnv, InstallStep::WriteDoctrineConfig],
        );

        $envVarReader = new ArrayEnvVarReader();
        $collector = new ParameterCollector($envVarReader);

        $installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        // Only CollectAndValidate should fire — WriteEnv and WriteDoctrineConfig are skipped
        $this->assertCount(1, $receivedSteps);
        $this->assertSame(InstallStep::CollectAndValidate, $receivedSteps[0]);
        $this->assertNotContains(InstallStep::WriteEnv, $receivedSteps);
        $this->assertNotContains(InstallStep::WriteDoctrineConfig, $receivedSteps);
    }

    /**
     * Creates a mock profile implementing both InstallProfileInterface and InstallStepFilterInterface.
     *
     * @param list<EnvVarDefinitionInterface> $definitions
     * @param list<InstallStep> $skippedSteps
     * @param list<class-string> $bundles
     */
    private function createMockProfileWithSkippedSteps(
        array $definitions,
        array $skippedSteps,
        array $bundles = [],
        bool $includeDefaultMarkerDefs = true,
    ): InstallProfileInterface {
        $allDefs = $this->resolveDefinitionsWithMarkerDefs($definitions, $includeDefaultMarkerDefs);

        return new class($allDefs, $bundles, $skippedSteps) implements InstallProfileInterface, InstallStepFilterInterface {
            /**
             * @param list<EnvVarDefinitionInterface> $definitions
             * @param list<class-string> $bundles
             * @param list<InstallStep> $skippedSteps
             */
            public function __construct(
                private readonly array $definitions,
                private readonly array $bundles,
                private readonly array $skippedSteps,
            ) {
            }

            public function getName(): string
            {
                return 'test-profile-with-skipped-steps';
            }

            public function getDescription(): string
            {
                return 'Test profile with skipped install steps';
            }

            public function getBundles(): array
            {
                return $this->bundles;
            }

            public function getEnvVarDefinitions(): array
            {
                return $this->definitions;
            }

            public function getDataSource(): ?DataSourceInterface
            {
                return null;
            }

            public function getPostInstallCommands(): array
            {
                return [];
            }

            public function getSkippedInstallSteps(): array
            {
                return $this->skippedSteps;
            }
        };
    }

    // -----------------------------------------------------------------------
    // Helper methods
    // -----------------------------------------------------------------------

    /**
     * Resolves definitions with optional marker definitions for search engine and messenger transport.
     *
     * @param list<EnvVarDefinitionInterface> $definitions
     *
     * @return list<EnvVarDefinitionInterface>
     */
    private function resolveDefinitionsWithMarkerDefs(
        array $definitions,
        bool $includeDefaultMarkerDefs,
    ): array {
        if (!$includeDefaultMarkerDefs) {
            return $definitions;
        }

        $hasSearchEngine = false;
        $hasMessengerTransport = false;

        foreach ($definitions as $def) {
            if ($def instanceof SearchEngineDefinitionInterface) {
                $hasSearchEngine = true;
            }
            if ($def instanceof MessengerTransportDefinitionInterface) {
                $hasMessengerTransport = true;
            }
        }

        if (!$hasSearchEngine) {
            $definitions[] = $this->createNoopSearchEngineDefinition();
        }
        if (!$hasMessengerTransport) {
            $definitions[] = $this->createNoopMessengerTransportDefinition();
        }

        return $definitions;
    }

    /**
     * Creates a mock definition that passes validation.
     *
     * @param list<ConfigParameter> $parameters
     * @param list<string> $resolvedEnvVarNames
     */
    private function createMockDefinition(
        string $key,
        bool $required,
        array $parameters,
        array $resolvedEnvVarNames,
        string $sectionName = 'test',
    ): EnvVarDefinitionInterface {
        return new class($key, $required, $parameters, $resolvedEnvVarNames, $sectionName)
            implements EnvVarDefinitionInterface
        {
            public function __construct(
                private readonly string $key,
                private readonly bool $required,
                private readonly array $parameters,
                private readonly array $resolvedEnvVarNames,
                private readonly string $sectionName,
            ) {
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function getLabel(): string
            {
                return ucfirst($this->key);
            }

            public function isRequired(): bool
            {
                return $this->required;
            }

            public function getSectionName(): string
            {
                return $this->sectionName;
            }

            public function getParameters(): array
            {
                return $this->parameters;
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                $result = [];
                foreach ($this->resolvedEnvVarNames as $name) {
                    $result[$name] = $collectedValues[$name] ?? '';
                }

                return $result;
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };
    }

    /**
     * Creates a definition whose validate() always returns errors.
     *
     * @param list<ConfigParameter> $parameters
     * @param list<string> $validationErrors
     */
    private function createFailingDefinition(
        string $key,
        bool $required,
        array $parameters,
        array $validationErrors,
    ): EnvVarDefinitionInterface {
        return new class($key, $required, $parameters, $validationErrors)
            implements EnvVarDefinitionInterface
        {
            public function __construct(
                private readonly string $key,
                private readonly bool $required,
                private readonly array $parameters,
                private readonly array $validationErrors,
            ) {
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function getLabel(): string
            {
                return ucfirst($this->key);
            }

            public function isRequired(): bool
            {
                return $this->required;
            }

            public function getSectionName(): string
            {
                return 'test';
            }

            public function getParameters(): array
            {
                return $this->parameters;
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                $result = [];
                foreach ($this->parameters as $param) {
                    $result[$param->getEnvVarName()] = $collectedValues[$param->getEnvVarName()] ?? '';
                }

                return $result;
            }

            public function validate(array $collectedValues): array
            {
                return $this->validationErrors;
            }
        };
    }

    /**
     * @param list<EnvVarDefinitionInterface> $definitions
     * @param list<EnvVarDefinitionInterface> $extraDefinitions Additional defs (appended to $definitions)
     * @param list<class-string> $bundles
     * @param bool $includeDefaultMarkerDefs Whether to auto-include lightweight
     *                                       SearchEngine + MessengerTransport marker definitions
     */
    private function createMockProfile(
        array $definitions,
        array $extraDefinitions = [],
        array $bundles = [],
        bool $includeDefaultMarkerDefs = true,
    ): InstallProfileInterface {
        $allDefs = $this->resolveDefinitionsWithMarkerDefs(
            array_merge($definitions, $extraDefinitions),
            $includeDefaultMarkerDefs,
        );

        return new class($allDefs, $bundles) implements InstallProfileInterface {
            public function __construct(
                private readonly array $definitions,
                private readonly array $bundles,
            ) {
            }

            public function getName(): string
            {
                return 'test-profile';
            }

            public function getDescription(): string
            {
                return 'Test profile for unit tests';
            }

            public function getBundles(): array
            {
                return $this->bundles;
            }

            public function getEnvVarDefinitions(): array
            {
                return $this->definitions;
            }

            public function getDataSource(): ?DataSourceInterface
            {
                return null;
            }

            public function getPostInstallCommands(): array
            {
                return [];
            }
        };
    }

    /**
     * Creates a lightweight SearchEngineDefinitionInterface that requires no input
     * and always passes validation. Used by createMockProfile() to satisfy
     * the category validation when the test doesn't care about search engines.
     */
    private function createNoopSearchEngineDefinition(): NoopSearchEngineDefinition
    {
        return new NoopSearchEngineDefinition();
    }

    private function createNoopMessengerTransportDefinition(): NoopMessengerTransportDefinition
    {
        return new NoopMessengerTransportDefinition();
    }
}
