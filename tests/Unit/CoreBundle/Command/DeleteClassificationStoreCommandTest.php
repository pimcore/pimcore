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

use Exception;
use Pimcore\Bundle\CoreBundle\Command\DeleteClassificationStoreCommand;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;
use Throwable;

/**
 * Value deletion goes through every registered engine, so that a store on a non-default engine
 * cannot report success while nothing was deleted.
 */
class DeleteClassificationStoreCommandTest extends TestCase
{
    /**
     * No store carries this id, so the configuration-table deletes below are no-ops.
     */
    private const STORE_ID = 2147483646;

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        $this->cleanupDbInSetup = false;

        parent::setUp();
    }

    public function testValueDeletionRunsOnEveryEngineBeforeTheConfigurationTables(): void
    {
        $calls = [];
        $engines = [
            'eav' => $this->recordingEngine('eav', $calls),
            'other' => $this->recordingEngine('other', $calls),
        ];

        [$exitCode, $output] = $this->runCommand($engines);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame([self::STORE_ID, self::STORE_ID], array_values($calls));
        $this->assertSame(['eav', 'other'], array_keys($calls));

        $this->assertStringContainsString('Deleting values for store ' . self::STORE_ID . ' via engine eav', $output);
        $this->assertStringContainsString('Deleting values for store ' . self::STORE_ID . ' via engine other', $output);

        // the four configuration-table lines are unchanged
        foreach (['keys', 'groups', 'collections'] as $table) {
            $this->assertStringContainsString(
                'DELETE FROM classificationstore_' . $table . ' WHERE storeId = ' . self::STORE_ID,
                $output
            );
        }
        $this->assertStringContainsString(
            'DELETE FROM classificationstore_stores WHERE id = ' . self::STORE_ID,
            $output
        );

        // the per-table value SQL the command used to echo is gone by construction
        $this->assertStringNotContainsString('DELETE FROM object_classificationstore_', $output);
    }

    public function testAFailingEngineStopsTheCommandWithANonZeroExitCode(): void
    {
        $failing = $this->createMock(EngineInterface::class);
        $failing->method('deleteStoreValues')->willThrowException(new Exception('engine is unavailable'));

        [$exitCode] = $this->runCommand(['eav' => $failing]);

        $this->assertNotSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Run through an Application, so that the exit code is the one an operator sees.
     *
     * @param array<string, EngineInterface> $engines
     *
     * @return array{0: int, 1: string}
     */
    private function runCommand(array $engines): array
    {
        TestHelper::checkDbSupport();

        $application = new Application();
        $application->setAutoExit(false);
        $application->add(new DeleteClassificationStoreCommand(new EngineResolver($engines)));

        $tester = new ApplicationTester($application);

        // the command reports its progress with echo, which does not reach the console output
        ob_start();

        try {
            $exitCode = $tester->run([
                'command' => 'pimcore:classificationstore:delete-store',
                'storeId' => (string) self::STORE_ID,
            ]);
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return [$exitCode, (string) ob_get_clean()];
    }

    /**
     * @param array<string, int> $calls
     */
    private function recordingEngine(string $name, array &$calls): EngineInterface
    {
        $engine = $this->createMock(EngineInterface::class);
        $engine->method('deleteStoreValues')
            ->willReturnCallback(static function (int $storeId) use ($name, &$calls): void {
                $calls[$name] = $storeId;
            });

        return $engine;
    }
}
