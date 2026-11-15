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

namespace Pimcore\Tests\Unit\Messenger\Handler;

use Doctrine\Persistence\ConnectionRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Messenger\Handler\AssetUpdateTasksHandler;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

class AssetUpdateTasksHandlerTest extends TestCase
{
    private const ASSET_ID = 987654321;

    private LockFactory $lockFactory;

    private LongRunningHelper $longRunningHelper;

    protected function setUp(): void
    {
        parent::setUp();

        Version::enable();

        $this->lockFactory = new LockFactory(new InMemoryStore());
        $this->longRunningHelper = new LongRunningHelper($this->createMock(ConnectionRegistry::class));
    }

    protected function tearDown(): void
    {
        Version::enable();
        RuntimeCache::getInstance()->offsetUnset($this->assetCacheKey());

        parent::tearDown();
    }

    public function testPdfScanResultIsSavedOnceWithoutVersionNote(): void
    {
        $document = $this->createDocument(scanPerformed: true);

        $saveCalls = [];
        $document->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (mixed ...$arguments) use (&$saveCalls, $document) {
                // the mock reports the default of save(array $parameters = []) as a passed argument
                $saveCalls[] = ['parameters' => $arguments[0] ?? [], 'versioningEnabled' => Version::isEnabled()];

                return $document;
            });

        $this->handle($document);

        // the save runs with versioning disabled, so a note could never reach a version
        self::assertSame([['parameters' => [], 'versioningEnabled' => false]], $saveCalls);
        self::assertTrue(Version::isEnabled(), 'Versioning must be enabled again after the save');
    }

    public function testDocumentIsNotSavedWhenNothingChanged(): void
    {
        $document = $this->createDocument(scanPerformed: false, pageCount: 3);
        $document->expects(self::never())->method('save');

        $this->handle($document);

        self::assertTrue(Version::isEnabled());
    }

    public function testClearedProcessingFailureMarkerIsPersisted(): void
    {
        $document = $this->createDocument(scanPerformed: false, failureMarker: true);
        $document->expects(self::once())->method('save')->willReturnSelf();

        $this->handle($document);

        self::assertNull($document->getCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED));
    }

    public function testVersioningIsEnabledAgainWhenSaveFails(): void
    {
        $document = $this->createDocument(scanPerformed: true);
        $document->expects(self::once())
            ->method('save')
            ->willThrowException(new RuntimeException('save failed'));

        try {
            $this->handle($document);
            self::fail('The exception of the failing save must not be swallowed');
        } catch (RuntimeException $e) {
            self::assertSame('save failed', $e->getMessage());
        }

        self::assertTrue(Version::isEnabled(), 'A failing save must not leave versioning disabled for the worker');
    }

    public function testVersioningDisabledByTheCallerStaysDisabledAfterSave(): void
    {
        Version::disable();

        $document = $this->createDocument(scanPerformed: true);
        $document->expects(self::once())->method('save')->willReturnSelf();

        $this->handle($document);

        self::assertFalse(Version::isEnabled(), 'The versioning state of the caller must be restored');
    }

    public function testVersioningDisabledByTheCallerStaysDisabledWhenSaveFails(): void
    {
        Version::disable();

        $document = $this->createDocument(scanPerformed: true);
        $document->expects(self::once())
            ->method('save')
            ->willThrowException(new RuntimeException('save failed'));

        try {
            $this->handle($document);
            self::fail('The exception of the failing save must not be swallowed');
        } catch (RuntimeException $e) {
            self::assertSame('save failed', $e->getMessage());
        }

        self::assertFalse(Version::isEnabled(), 'The versioning state of the caller must be restored');
    }

    public function testTemporaryFilesAreRemovedWhenProcessingFails(): void
    {
        $document = $this->createDocument(scanPerformed: false, pageCountProcessing: true);
        $document->method('processPageCount')->willThrowException(new RuntimeException('processing failed'));

        $temporaryFile = tempnam(sys_get_temp_dir(), 'pimcore-asset-update-test');
        self::assertNotFalse($temporaryFile);
        $this->longRunningHelper->addTmpFilePath($temporaryFile);

        try {
            $this->handle($document);
            self::fail('The processing exception must not be swallowed');
        } catch (RuntimeException $e) {
            self::assertSame('processing failed', $e->getMessage());
        }

        self::assertFileDoesNotExist($temporaryFile, 'Temporary files must be removed even if processing failed');
    }

    /**
     * @return Asset\Document&MockObject
     */
    private function createDocument(
        bool $scanPerformed,
        ?int $pageCount = null,
        bool $pageCountProcessing = false,
        bool $failureMarker = false,
    ): Asset\Document {
        $document = $this->getMockBuilder(Asset\Document::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId',
                'getRealFullPath',
                'getMimeType',
                'checkIfPdfContainsJS',
                'isPageCountProcessingEnabled',
                'isThumbnailsEnabled',
                'processPageCount',
                'markFieldDirty',
                'save',
            ])
            ->getMock();

        $document->method('getId')->willReturn(self::ASSET_ID);
        $document->method('getRealFullPath')->willReturn('/test.pdf');
        $document->method('getMimeType')->willReturn('application/pdf');
        $document->method('checkIfPdfContainsJS')->willReturn($scanPerformed);
        $document->method('isPageCountProcessingEnabled')->willReturn($pageCountProcessing || $pageCount !== null);
        $document->method('isThumbnailsEnabled')->willReturn(false);

        if ($pageCount !== null) {
            $document->setCustomSetting('document_page_count', $pageCount);
        }
        if ($failureMarker) {
            $document->setCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED, true);
        }

        RuntimeCache::set($this->assetCacheKey(), $document);

        return $document;
    }

    private function handle(Asset\Document $document): void
    {
        $handler = new AssetUpdateTasksHandler(new NullLogger(), $this->longRunningHelper, $this->lockFactory);
        $handler(new AssetUpdateTasksMessage((int) $document->getId()));
    }

    private function assetCacheKey(): string
    {
        return (new ReflectionMethod(Asset::class, 'getCacheKey'))->invoke(null, self::ASSET_ID);
    }
}
