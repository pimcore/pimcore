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

namespace Pimcore\Tests\Unit\CoreBundle\Command;

use Pimcore\Bundle\CoreBundle\Command\CheckClassificationStoreEnginesCommand;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * A broken engine value should fail a deployment check rather than a user's first object load.
 */
class CheckClassificationStoreEnginesCommandTest extends TestCase
{
    public function testEveryUnresolvableStoreIsReportedAndTheCommandFails(): void
    {
        $tester = new CommandTester($this->command([
            $this->storeConfig(1, 'healthy', null),
            $this->storeConfig(2, 'broken', 'nosuchengine'),
            $this->storeConfig(3, 'queued', 'document'),
        ]));

        $this->assertSame(Command::FAILURE, $tester->execute([]));

        $display = $this->normalize($tester->getDisplay());

        $this->assertStringContainsString('Classification store 2', $display);
        $this->assertStringContainsString('"nosuchengine", which is not registered', $display);
        $this->assertStringContainsString('Classification store 3', $display);
        $this->assertStringContainsString('"document", which this Pimcore version does not ship yet', $display);
        $this->assertStringContainsString('store name: "broken"', $display);
        $this->assertStringContainsString('store name: "queued"', $display);
        $this->assertStringNotContainsString('Classification store 1', $display);
    }

    public function testAHealthyInstallationReportsNothingAndSucceeds(): void
    {
        $tester = new CommandTester($this->command([
            $this->storeConfig(1, 'healthy', null),
            $this->storeConfig(2, 'explicit', 'eav'),
        ]));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString(
            'All classification stores resolve to a registered engine.',
            $this->normalize($tester->getDisplay())
        );
    }

    /**
     * @param StoreConfig[] $storeConfigs
     */
    private function command(array $storeConfigs): CheckClassificationStoreEnginesCommand
    {
        $resolver = new EngineResolver(['eav' => $this->createMock(EngineInterface::class)]);

        return new class($resolver, $storeConfigs) extends CheckClassificationStoreEnginesCommand {
            /**
             * @param StoreConfig[] $storeConfigs
             */
            public function __construct(EngineResolver $engineResolver, private readonly array $storeConfigs)
            {
                parent::__construct($engineResolver);
            }

            protected function loadStoreConfigs(): array
            {
                return $this->storeConfigs;
            }
        };
    }

    private function storeConfig(int $id, string $name, ?string $engine): StoreConfig
    {
        $storeConfig = new StoreConfig();
        $storeConfig->setId($id);
        $storeConfig->setName($name);
        $storeConfig->setEngine($engine);

        return $storeConfig;
    }

    /**
     * The console wraps its blocks to the terminal width, so the messages are compared unwrapped.
     */
    private function normalize(string $display): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $display));
    }
}
