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

use Pimcore\Document\Adapter\Ghostscript;
use Pimcore\File;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Ghostscript exits successfully without writing an image when asked for a page the document does not have.
 * saveImage() used to report that as success, so ImageThumbnail read a temp file that did not exist and raised
 * "file_get_contents(...): Failed to open stream" before falling back to the placeholder.
 *
 * The fixture, sonnenblume.pdf, has a single page.
 *
 * @group model.asset.document-image-thumbnail
 */
final class DocumentImageThumbnailTest extends ModelTestCase
{
    private const PLACEHOLDER = '/bundles/pimcoreadmin/img/filetype-not-supported.svg';

    protected function setUp(): void
    {
        parent::setUp();

        if (!(new Ghostscript())->isAvailable()) {
            $this->markTestSkipped('Ghostscript is not available');
        }
    }

    protected function tearDown(): void
    {
        TestHelper::clearThumbnailConfigurations();

        parent::tearDown();
    }

    public function testSaveImageReportsWhetherAnImageWasWritten(): void
    {
        $adapter = (new Ghostscript())->load(TestHelper::createDocumentAsset());

        $existingPage = File::getLocalTempFilePath('png');
        $this->assertTrue($adapter->saveImage($existingPage, 1, 72));
        $this->assertGreaterThan(0, filesize($existingPage));

        $missingPage = File::getLocalTempFilePath('png');
        $this->assertFalse($adapter->saveImage($missingPage, 2, 72));
        $this->assertFileDoesNotExist($missingPage);
    }

    public function testThumbnailOfMissingPageFallsBackToPlaceholderWithoutWarning(): void
    {
        $thumbnail = new Asset\Document\ImageThumbnail(
            TestHelper::createDocumentAsset(),
            TestHelper::createThumbnailConfigurationScaleByWidth(),
            2,
            false
        );

        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        }, E_WARNING);

        try {
            $path = $thumbnail->getPath(['deferredAllowed' => false, 'frontend' => false]);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertSame(self::PLACEHOLDER, $path);
    }
}
