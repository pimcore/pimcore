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

namespace Pimcore\Tests\Unit\InstallBundle\Integration;

use Pimcore\Bundle\InstallBundle\Collector\ArrayEnvVarReader;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\DatabaseEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\DoctrineMessengerEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\MercureEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\OpenSearchEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Bundle\InstallBundle\Profile\DataSource\DataSourceInterface;
use Pimcore\Bundle\InstallBundle\Profile\InstallProfileInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Unit\InstallBundle\Support\InstallBundleTestHelperTrait;
use Pimcore\Tests\Unit\InstallBundle\Support\NoopMessengerTransportDefinition;
use Pimcore\Tests\Unit\InstallBundle\Support\NoopSearchEngineDefinition;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * End-to-end integration test for Phase 1 using real definition classes.
 *
 * Uses DatabaseEnvVarDefinition, OpenSearchEnvVarDefinition,
 * DoctrineMessengerEnvVarDefinition, and MercureEnvVarDefinition with
 * ArrayEnvVarReader to pre-populate env vars. Verifies the complete flow:
 * profile → definitions → collection → validation → .env.local output.
 *
 * Note: Validation for Database and OpenSearch definitions attempts real
 * connections. We bypass this by providing the DSN env var directly
 * or by wrapping the definition to skip connection testing.
 *
 * @internal
 */
final class InstallerProfilesIntegrationTest extends TestCase
{
    use InstallBundleTestHelperTrait;

    private string $tempDir;

    private Installer $installer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/pimcore_phase1_integration_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->installer = $this->createInstaller();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testPhaseOneWithRealDefinitionsWritesCorrectEnvLocal(): void
    {
        $envVarReader = new ArrayEnvVarReader();

        // Pre-set env vars for all definitions
        $envVarReader->set('DATABASE_URL', 'mysql://pimcore:secret@db:3306/pimcore');
        $envVarReader->set('PIMCORE_OPENSEARCH_DSN', 'opensearch://admin:admin@opensearch:9200?ssl_verify=false');
        $envVarReader->set('PIMCORE_MESSENGER_TRANSPORT_DSN', 'doctrine://default');
        $envVarReader->set('MERCURE_JWT_KEY', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4');
        $envVarReader->set('MERCURE_URL', 'http://localhost/hub');
        $envVarReader->set('MERCURE_SERVER_URL', 'http://mercure/.well-known/mercure');

        $profile = $this->createNonValidatingProfile();
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

        $this->assertSame([], $errors, 'Phase 1 should complete without errors');
        $this->assertFileExists($this->tempDir . '/.env.local');

        $envContent = file_get_contents($this->tempDir . '/.env.local');

        // Verify section markers for each definition's section name
        $this->assertStringContainsString('###> pimcore/pimcore ###', $envContent);
        $this->assertStringContainsString('###< pimcore/pimcore ###', $envContent);
        $this->assertStringContainsString('###> pimcore/opensearch-client ###', $envContent);
        $this->assertStringContainsString('###< pimcore/opensearch-client ###', $envContent);
        $this->assertStringContainsString('###> pimcore/studio-backend-bundle ###', $envContent);
        $this->assertStringContainsString('###< pimcore/studio-backend-bundle ###', $envContent);

        // Verify env var values
        $this->assertStringContainsString('DATABASE_URL="mysql://pimcore:secret@db:3306/pimcore"', $envContent);
        $this->assertStringContainsString('PIMCORE_MESSENGER_TRANSPORT_DSN="doctrine://default?queue_name="', $envContent);
        $this->assertStringContainsString('PIMCORE_OPENSEARCH_DSN="opensearch://admin:admin@opensearch:9200?ssl_verify=false"', $envContent);
        $this->assertStringContainsString('MERCURE_JWT_KEY="a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4"', $envContent);
        $this->assertStringContainsString('MERCURE_URL="http://localhost/hub"', $envContent);
        $this->assertStringContainsString('MERCURE_SERVER_URL="http://mercure/.well-known/mercure"', $envContent);
    }

    public function testPhaseOneWithDatabaseUrlPassedThroughDirectly(): void
    {
        $envVarReader = new ArrayEnvVarReader();

        // Set DATABASE_URL directly — the definition now collects it as a single parameter.
        $envVarReader->set('DATABASE_URL', 'mysql://myuser:mypass@10.0.0.1:3307/mydb');

        // Use a non-validating database definition to avoid actual DB connection
        $dbDef = $this->createNonValidatingDatabaseDefinition();

        // Required marker defs
        $searchDef = $this->createNoopSearchEngineDefinition();
        $messengerDef = $this->createNoopMessengerTransportDefinition();

        $profile = $this->createMockProfile([$dbDef, $searchDef, $messengerDef]);
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

        // Verify the DATABASE_URL was written directly as provided
        $this->assertStringContainsString(
            'DATABASE_URL="mysql://myuser:mypass@10.0.0.1:3307/mydb"',
            $envContent,
        );
    }

    public function testPhaseOneGroupsEnvVarsBySectionName(): void
    {
        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('DATABASE_URL', 'mysql://user:pass@localhost/db');
        $envVarReader->set('PIMCORE_OPENSEARCH_DSN', 'opensearch://localhost:9200?ssl_verify=false');
        $envVarReader->set('PIMCORE_MESSENGER_TRANSPORT_DSN', 'doctrine://default');
        $envVarReader->set('MERCURE_JWT_KEY', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4');
        $envVarReader->set('MERCURE_URL', 'http://localhost/hub');
        $envVarReader->set('MERCURE_SERVER_URL', 'http://mercure/.well-known/mercure');

        $profile = $this->createNonValidatingProfile();
        $collector = new ParameterCollector($envVarReader);

        $this->installer->runPhaseOne(
            $profile,
            [],
            [],
            ['username' => 'admin', 'password' => 'admin123'],
            $collector,
            $this->createNonInteractiveIo(),
            false,
            $this->tempDir,
        );

        $envContent = file_get_contents($this->tempDir . '/.env.local');

        // DATABASE_URL and PIMCORE_MESSENGER_TRANSPORT_DSN should be
        // in the same pimcore/pimcore section
        $pimcoreSection = $this->extractSection($envContent, 'pimcore/pimcore');
        $this->assertStringContainsString('DATABASE_URL=', $pimcoreSection);
        $this->assertStringContainsString('PIMCORE_MESSENGER_TRANSPORT_DSN=', $pimcoreSection);

        // OpenSearch DSN should be in the opensearch-client section
        $opensearchSection = $this->extractSection($envContent, 'pimcore/opensearch-client');
        $this->assertStringContainsString('PIMCORE_OPENSEARCH_DSN=', $opensearchSection);

        // Mercure env vars should be in the studio-backend-bundle section
        $mercureSection = $this->extractSection($envContent, 'pimcore/studio-backend-bundle');
        $this->assertStringContainsString('MERCURE_JWT_KEY=', $mercureSection);
        $this->assertStringContainsString('MERCURE_URL=', $mercureSection);
        $this->assertStringContainsString('MERCURE_SERVER_URL=', $mercureSection);
    }

    public function testPhaseOneWithSkippedOptionalDefinition(): void
    {
        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('DATABASE_URL', 'mysql://user:pass@localhost/db');
        $envVarReader->set('PIMCORE_OPENSEARCH_DSN', 'opensearch://localhost:9200?ssl_verify=false');
        $envVarReader->set('PIMCORE_MESSENGER_TRANSPORT_DSN', 'doctrine://default');
        $envVarReader->set('MERCURE_JWT_KEY', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4');
        $envVarReader->set('MERCURE_URL', 'http://localhost/hub');
        $envVarReader->set('MERCURE_SERVER_URL', 'http://mercure/.well-known/mercure');

        // Add an optional definition that should be skippable
        $optionalDef = $this->createMockDefinition(
            'redis',
            false,
            [new ConfigParameter('REDIS_URL', 'Redis URL', ParameterType::Url, defaultValue: 'redis://localhost')],
            ['REDIS_URL'],
        );

        $profile = $this->createNonValidatingProfileWithExtra([$optionalDef]);
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

        // Redis should NOT be in .env.local (optional, no env var set, non-interactive)
        $this->assertStringNotContainsString('REDIS_URL', $envContent);

        // But required definitions should still be present
        $this->assertStringContainsString('DATABASE_URL=', $envContent);
        $this->assertStringContainsString('PIMCORE_MESSENGER_TRANSPORT_DSN=', $envContent);
    }

    public function testPhaseOneEventsAreDispatched(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatchedSteps = [];

        $dispatcher->addListener(
            'pimcore.installer.step',
            function ($event) use (&$dispatchedSteps): void {
                $dispatchedSteps[] = $event->getType();
            },
        );

        $installer = $this->createInstaller(eventDispatcher: $dispatcher);

        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('DATABASE_URL', 'mysql://user:pass@localhost/db');
        $envVarReader->set('PIMCORE_OPENSEARCH_DSN', 'opensearch://localhost:9200?ssl_verify=false');
        $envVarReader->set('PIMCORE_MESSENGER_TRANSPORT_DSN', 'doctrine://default');
        $envVarReader->set('MERCURE_JWT_KEY', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4');
        $envVarReader->set('MERCURE_URL', 'http://localhost/hub');
        $envVarReader->set('MERCURE_SERVER_URL', 'http://mercure/.well-known/mercure');

        $profile = $this->createNonValidatingProfile();
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

        // Phase 1 dispatches steps for 'collect_validate' and 'write_env'
        $this->assertContains('collect_validate', $dispatchedSteps);
        $this->assertContains('write_env', $dispatchedSteps);
    }

    /**
     * Create a profile using real definition classes but with connection
     * testing disabled (validate() returns [] for all definitions).
     * This allows testing the collection/writing flow without real services.
     */
    private function createNonValidatingProfile(): InstallProfileInterface
    {
        $definitions = [
            $this->createNonValidatingDatabaseDefinition(),
            $this->createNonValidatingOpenSearchDefinition(),
            new DoctrineMessengerEnvVarDefinition(),
            new MercureEnvVarDefinition(),
        ];

        return $this->createMockProfile($definitions);
    }

    /**
     * Create a non-validating profile with extra definitions appended.
     *
     * @param list<EnvVarDefinitionInterface> $extraDefs
     */
    private function createNonValidatingProfileWithExtra(array $extraDefs): InstallProfileInterface
    {
        $definitions = array_merge([
            $this->createNonValidatingDatabaseDefinition(),
            $this->createNonValidatingOpenSearchDefinition(),
            new DoctrineMessengerEnvVarDefinition(),
            new MercureEnvVarDefinition(),
        ], $extraDefs);

        return $this->createMockProfile($definitions);
    }

    /**
     * Creates a mock profile from provided definitions (no auto-inclusion of marker defs).
     *
     * @param list<EnvVarDefinitionInterface> $definitions
     */
    private function createMockProfile(array $definitions): InstallProfileInterface
    {
        return new class($definitions) implements InstallProfileInterface {
            public function __construct(private readonly array $definitions)
            {
            }

            public function getName(): string
            {
                return 'mock-profile';
            }

            public function getDescription(): string
            {
                return 'Mock profile';
            }

            public function getBundles(): array
            {
                return [];
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
     * Creates a database definition that uses the same resolveEnvVars() logic
     * as DatabaseEnvVarDefinition but skips connection testing in validate().
     */
    private function createNonValidatingDatabaseDefinition(): EnvVarDefinitionInterface
    {
        return new class() implements EnvVarDefinitionInterface {
            private readonly DatabaseEnvVarDefinition $inner;

            public function __construct()
            {
                $this->inner = new DatabaseEnvVarDefinition();
            }

            public function getKey(): string
            {
                return $this->inner->getKey();
            }

            public function getLabel(): string
            {
                return $this->inner->getLabel();
            }

            public function isRequired(): bool
            {
                return $this->inner->isRequired();
            }

            public function getSectionName(): string
            {
                return $this->inner->getSectionName();
            }

            public function getParameters(): array
            {
                return $this->inner->getParameters();
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                return $this->inner->resolveEnvVars($collectedValues);
            }

            public function validate(array $collectedValues): array
            {
                // Skip connection testing — only validate format
                return [];
            }
        };
    }

    /**
     * Creates an OpenSearch definition that uses the same resolveEnvVars() logic
     * as OpenSearchEnvVarDefinition but skips connection testing in validate().
     */
    private function createNonValidatingOpenSearchDefinition(): SearchEngineDefinitionInterface
    {
        return new class() implements SearchEngineDefinitionInterface {
            private readonly OpenSearchEnvVarDefinition $inner;

            public function __construct()
            {
                $this->inner = new OpenSearchEnvVarDefinition();
            }

            public function getKey(): string
            {
                return $this->inner->getKey();
            }

            public function getLabel(): string
            {
                return $this->inner->getLabel();
            }

            public function isRequired(): bool
            {
                return $this->inner->isRequired();
            }

            public function getSectionName(): string
            {
                return $this->inner->getSectionName();
            }

            public function getParameters(): array
            {
                return $this->inner->getParameters();
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                return $this->inner->resolveEnvVars($collectedValues);
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };
    }

    /**
     * Creates a mock definition (simple, non-connecting).
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

    private function createNoopSearchEngineDefinition(): NoopSearchEngineDefinition
    {
        return new NoopSearchEngineDefinition();
    }

    private function createNoopMessengerTransportDefinition(): NoopMessengerTransportDefinition
    {
        return new NoopMessengerTransportDefinition();
    }

    /**
     * Extract the content between section markers for a given section name.
     */
    private function extractSection(string $content, string $sectionName): string
    {
        $openMarker = '###> ' . $sectionName . ' ###';
        $closeMarker = '###< ' . $sectionName . ' ###';

        $openPos = strpos($content, $openMarker);
        $closePos = strpos($content, $closeMarker);

        if ($openPos === false || $closePos === false || $closePos <= $openPos) {
            return '';
        }

        return substr($content, $openPos, $closePos + strlen($closeMarker) - $openPos);
    }
}
