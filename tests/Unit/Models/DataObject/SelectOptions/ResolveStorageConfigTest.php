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

namespace Pimcore\Tests\Unit\Model\DataObject\SelectOptions;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\SelectOptions\Config\Dao;

/**
 * The default read target of select options is symfony-config. With the settings store as write target
 * it must not hide the definitions saved there (pimcore/pimcore#16682).
 */
class ResolveStorageConfigTest extends TestCase
{
    public function testSettingsStoreWriteTargetWithDefaultReadTargetReadsBothSources(): void
    {
        $config = Dao::resolveStorageConfig($this->storageConfig('settings-store', 'symfony-config'));

        $this->assertNull($config['read_target']['type']);
        $this->assertSame('settings-store', $config['write_target']['type']);
    }

    /**
     * @dataProvider unchangedProvider
     */
    public function testOtherSetupsStayUnchanged(string $writeTarget, ?string $readTarget): void
    {
        $storageConfig = $this->storageConfig($writeTarget, $readTarget);

        $this->assertSame($storageConfig, Dao::resolveStorageConfig($storageConfig));
    }

    public static function unchangedProvider(): array
    {
        return [
            'default setup' => ['symfony-config', 'symfony-config'],
            'explicit settings-store read' => ['settings-store', 'settings-store'],
            'disabled write' => ['disabled', 'symfony-config'],
            'symfony-config write, store read' => ['symfony-config', 'settings-store'],
        ];
    }

    private function storageConfig(string $writeTarget, ?string $readTarget): array
    {
        return [
            'write_target' => ['type' => $writeTarget, 'options' => ['directory' => '/tmp/select_options']],
            'read_target' => ['type' => $readTarget, 'options' => ['directory' => '/tmp/select_options']],
        ];
    }
}
