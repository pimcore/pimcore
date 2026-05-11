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

use Pimcore\Bundle\CoreBundle\Command\CdnPurgeCommand;
use Pimcore\Cdn\PurgeClientInterface;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\CommandTester;

class CdnPurgeCommandTest extends TestCase
{
    /**
     * @return array{0: PurgeClientInterface, 1: \ArrayObject<string, array>}
     */
    private function makeClient(): array
    {
        $calls = new \ArrayObject(['purgeByTags' => []]);
        $client = $this->createMock(PurgeClientInterface::class);
        $client->method('purgeByTags')
            ->willReturnCallback(function (array $tags) use ($calls): void {
                $existing = $calls['purgeByTags'];
                $existing[] = $tags;
                $calls['purgeByTags'] = $existing;
            });

        return [$client, $calls];
    }

    /**
     * Returns a command that resolves the given id->fullPath map for asset lookups.
     * Unknown IDs resolve to null (simulates "asset not found").
     */
    private function makeCommandWithAssetMap(PurgeClientInterface $client, array $idToFullPath): CdnPurgeCommand
    {
        // Pre-build asset mocks here (test class has access to PHPUnit's mock builder).
        $assets = [];
        foreach ($idToFullPath as $id => $fullPath) {
            $assets[$id] = $this->makeAsset($fullPath);
        }

        return new class($client, $assets) extends CdnPurgeCommand {
            /** @param array<int, Asset> $assets */
            public function __construct(PurgeClientInterface $client, private readonly array $assets)
            {
                parent::__construct($client);
            }

            protected function loadAsset(int $id): ?Asset
            {
                return $this->assets[$id] ?? null;
            }
        };
    }

    private function makeAsset(string $fullPath): Asset
    {
        $asset = $this->getMockBuilder(Asset::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFullPath'])
            ->getMock();
        $asset->method('getFullPath')->willReturn($fullPath);

        return $asset;
    }

    private function runCommand(PurgeClientInterface $client, array $input, array $idToFullPath = []): CommandTester
    {
        $command = $this->makeCommandWithAssetMap($client, $idToFullPath);
        $tester = new CommandTester($command);
        $tester->execute($input, ['interactive' => false]);

        return $tester;
    }

    private function expectedPathHashTag(string $fullPath): string
    {
        return 'asset-path-' . substr(hash('sha256', '/var/assets' . $fullPath), 0, 12);
    }

    public function testAssetOptionPurgesAssetIdAndPathHashTags(): void
    {
        [$client, $calls] = $this->makeClient();
        $tester = $this->runCommand($client, ['--asset' => ['42']], [42 => '/products/image.jpg']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertCount(1, $calls['purgeByTags']);
        $this->assertSame(
            ['asset-42', $this->expectedPathHashTag('/products/image.jpg')],
            $calls['purgeByTags'][0],
        );
    }

    public function testAssetOptionWithUnknownIdStillPurgesIdTagAndWarns(): void
    {
        [$client, $calls] = $this->makeClient();
        $tester = $this->runCommand($client, ['--asset' => ['999']], []); // empty map -> not found

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame([['asset-999']], $calls['purgeByTags']);
        $this->assertStringContainsString('not found', $tester->getDisplay());
    }

    public function testConfigOptionPurgesThumbConfigTag(): void
    {
        [$client, $calls] = $this->makeClient();
        $this->runCommand($client, ['--config' => ['product-thumb']]);

        $this->assertSame([['thumb-product-thumb']], $calls['purgeByTags']);
    }

    public function testMultipleAssetsArePassedAsOneBatchCall(): void
    {
        [$client, $calls] = $this->makeClient();
        $map = [1 => '/a.jpg', 2 => '/b.jpg', 3 => '/c.jpg'];
        $this->runCommand($client, ['--asset' => ['1', '2', '3']], $map);

        $this->assertCount(1, $calls['purgeByTags']);
        $expected = [
            'asset-1', $this->expectedPathHashTag('/a.jpg'),
            'asset-2', $this->expectedPathHashTag('/b.jpg'),
            'asset-3', $this->expectedPathHashTag('/c.jpg'),
        ];
        $this->assertSame($expected, $calls['purgeByTags'][0]);
    }

    public function testAssetAndConfigOptionsAreCombinedIntoOneBatch(): void
    {
        [$client, $calls] = $this->makeClient();

        $this->runCommand($client, [
            '--asset' => ['10'],
            '--config' => ['hero'],
        ], [10 => '/x.jpg']);

        $this->assertCount(1, $calls['purgeByTags']);
        $allTags = $calls['purgeByTags'][0];
        $this->assertContains('asset-10', $allTags);
        $this->assertContains($this->expectedPathHashTag('/x.jpg'), $allTags);
        $this->assertContains('thumb-hero', $allTags);
    }

    public function testNoOptionsReturnsFailure(): void
    {
        [$client] = $this->makeClient();
        $tester = $this->runCommand($client, []);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    public function testNoOptionsDoesNotCallPurgeClient(): void
    {
        $client = $this->createMock(PurgeClientInterface::class);
        $client->expects($this->never())->method('purgeByTags');
        $client->expects($this->never())->method('purgeByUrl');

        $this->runCommand($client, []);
    }

    public function testHelpMentionsPurgeAllNotSupported(): void
    {
        $command = new CdnPurgeCommand($this->createMock(PurgeClientInterface::class));

        $this->assertStringContainsString('Purge-all is not supported', $command->getHelp());
    }

    public function testHelpPositionsCommandAsRecoveryAndAutomationTool(): void
    {
        $command = new CdnPurgeCommand($this->createMock(PurgeClientInterface::class));

        $help = $command->getHelp();
        $this->assertStringContainsString('recovery', strtolower($help));
        $this->assertStringContainsString('automation', strtolower($help));
        $this->assertStringContainsString('admin panel', strtolower($help));
    }

    public function testUrlOptionIsRemoved(): void
    {
        [$client] = $this->makeClient();
        $this->expectException(InvalidOptionException::class);

        $this->runCommand($client, ['--url' => ['https://cdn.example.com/img.jpg']]);
    }

    public function testTagOptionIsRemoved(): void
    {
        [$client] = $this->makeClient();
        $this->expectException(InvalidOptionException::class);

        $this->runCommand($client, ['--tag' => ['custom-tag']]);
    }

    public function testCommandNeverCallsPurgeByUrl(): void
    {
        $client = $this->createMock(PurgeClientInterface::class);
        $client->expects($this->never())->method('purgeByUrl');

        $this->runCommand($client, ['--asset' => ['1']], [1 => '/a.jpg']);
        $this->runCommand($client, ['--config' => ['hero']]);
    }

    public function testDuplicateAssetIdsAreDeduplicatedBeforePurge(): void
    {
        // Repeating the same --asset value would otherwise emit the asset-{id} and
        // asset-path-{hash} tags twice, bloating the Surrogate-Key header sent to the CDN.
        [$client, $calls] = $this->makeClient();
        $this->runCommand($client, ['--asset' => ['42', '42']], [42 => '/img.jpg']);

        $this->assertCount(1, $calls['purgeByTags']);
        $batch = $calls['purgeByTags'][0];
        $this->assertSame(count($batch), count(array_unique($batch)), 'Batch should contain no duplicate tags');
        $this->assertSame(
            ['asset-42', $this->expectedPathHashTag('/img.jpg')],
            $batch,
        );
    }

    public function testDuplicateConfigsAreDeduplicatedBeforePurge(): void
    {
        [$client, $calls] = $this->makeClient();
        $this->runCommand($client, ['--config' => ['hero', 'hero']]);

        $this->assertSame([['thumb-hero']], $calls['purgeByTags']);
    }
}
