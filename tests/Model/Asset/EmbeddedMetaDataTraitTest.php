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

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Model\Asset\MetaData\EmbeddedMetaDataTrait;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Console;

/**
 * @group model.asset.embeddedmetadata
 */
class EmbeddedMetaDataTraitTest extends ModelTestCase
{
    private const FILE_SYSTEM_KEYS = [
        'FileName',
        'FileDateTime',
        'FileAccessDate',
        'FileAttributes',
        'FileCreateDate',
        'FileInodeChangeDate',
        'FileModifyDate',
        'FilePermissions',
        'Directory',
        'SourceFile',
    ];

    public function testFallbackWithoutExifToolOmitsFileSystemData(): void
    {
        $metaData = $this->read(false);

        $this->assertNotEmpty($metaData);
        foreach (self::FILE_SYSTEM_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $metaData);
        }

        // data about the content of the file is kept
        $this->assertArrayHasKey('FileSize', $metaData);
        $this->assertSame('image/jpeg', $metaData['MimeType']);
    }

    public function testExifToolOmitsFileSystemData(): void
    {
        if (!Console::getExecutable('exiftool')) {
            $this->markTestSkipped('exiftool is not installed');
        }

        $metaData = $this->read(true);

        $this->assertNotEmpty($metaData);
        foreach (self::FILE_SYSTEM_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $metaData);
        }

        // data about the content of the file is kept
        $this->assertArrayHasKey('FileSize', $metaData);
        $this->assertSame('image/jpeg', $metaData['MIMEType']);
    }

    private function read(bool $useExifTool): array
    {
        $asset = new class(TestHelper::resolveFilePath('assets/images/image1.jpg')) {
            use EmbeddedMetaDataTrait {
                readEmbeddedMetaData as public;
            }

            public function __construct(private readonly string $file)
            {
            }

            public function getLocalFile(): string
            {
                return $this->file;
            }

            public function getId(): int
            {
                return 1;
            }

            public function setCustomSetting(string $key, mixed $value): void
            {
            }
        };

        return $asset->readEmbeddedMetaData($useExifTool);
    }
}
