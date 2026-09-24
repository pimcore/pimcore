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

namespace Pimcore\Tests\Unit\Models\Asset\Document;

use Pimcore\Model\Asset\Document;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Console;
use RuntimeException;

/**
 * Document assets support embedded meta data (#18478) by reusing the
 * EmbeddedMetaDataTrait which is also used by image and video assets.
 *
 * @group model.asset.document
 */
class EmbeddedMetaDataTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            @unlink($tempFile);
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    private function getFixturePath(): string
    {
        return TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf');
    }

    /**
     * Creates a file with an XMP open tag, followed by the given amount of bytes without a close tag
     */
    private function createFileWithUnclosedXmpPacket(int $bytesAfterOpenTag): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'pimcore-embedded-meta-data-test-');
        $this->tempFiles[] = $filePath;

        $handle = fopen($filePath, 'wb');
        fwrite($handle, "%PDF-1.4\n<x:xmpmeta xmlns:x=\"adobe:ns:meta/\">\n");
        $chunk = str_repeat('x', 1024 * 1024);
        for ($written = 0; $written < $bytesAfterOpenTag; $written += strlen($chunk)) {
            fwrite($handle, substr($chunk, 0, min(strlen($chunk), $bytesAfterOpenTag - $written)));
        }
        fclose($handle);

        return $filePath;
    }

    public function testEmbeddedMetaDataIsReadFromXmpPacket(): void
    {
        $document = new Document();
        $this->assertSame([], $document->getEmbeddedMetaData(false));
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        $document->handleEmbeddedMetaData(false, $this->getFixturePath());

        $metaData = $document->getEmbeddedMetaData(false);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        $this->assertSame('Pimcore Test Suite', $metaData['Producer']);
        $this->assertSame('pimcore, embedded, metadata', $metaData['Keywords']);
        $this->assertStringContainsString('Embedded Meta Data Test', $metaData['title']);
        $this->assertStringContainsString('Pimcore', $metaData['creator']);
    }

    public function testEmbeddedMetaDataIsReadWithExifTool(): void
    {
        if (!Console::getExecutable('exiftool')) {
            $this->markTestSkipped('exiftool is not available');
        }

        $document = new Document();
        $document->handleEmbeddedMetaData(true, $this->getFixturePath());

        $metaData = $document->getEmbeddedMetaData(false);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Embedded Meta Data Test', $metaData['Title']);
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        // list values are flattened
        $this->assertSame('pimcore | embedded | metadata', $metaData['Keywords']);
        $this->assertSame('pimcore | embedded | metadata', $metaData['Subject']);

        foreach (['Directory', 'FileName', 'SourceFile', 'ExifToolVersion'] as $removedKey) {
            $this->assertArrayNotHasKey($removedKey, $metaData);
        }
    }

    /**
     * The file is read in chunks of 1024 bytes. If the open tag ends less than the length of the close tag
     * before the end of a chunk, the close tag must still be found in the next chunk
     */
    public function testXmpPacketIsFoundIfOpenTagIsAtTheEndOfAChunk(): void
    {
        $packet = '<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            . '<rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Chunk Test</dc:title>'
            . '</rdf:Description></rdf:RDF></x:xmpmeta>';
        $document = new Document();

        // 1012: the buffer is exactly as long as the close tag, 1013 and 1014: it is shorter
        foreach ([1012, 1013, 1014] as $openTagPosition) {
            $filePath = tempnam(sys_get_temp_dir(), 'pimcore-embedded-meta-data-test-');
            $this->tempFiles[] = $filePath;
            file_put_contents($filePath, str_repeat('a', $openTagPosition) . $packet . "\n%%EOF\n");

            $data = $document->getXMPData($filePath);
            $this->assertSame('Chunk Test', $data['title'] ?? null, 'open tag at position ' . $openTagPosition);
        }
    }

    public function testXmpPacketWithoutCloseTagIsAbortedCleanly(): void
    {
        $filePath = $this->createFileWithUnclosedXmpPacket(4 * 1024);
        $document = new Document();

        try {
            $document->getXMPData($filePath);
            $this->fail('Expected an exception for an XMP packet without close tag');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No close tag found', $e->getMessage());
        }

        // the extraction itself doesn't fail, the file just has no usable embedded meta data
        $document->handleEmbeddedMetaData(false, $filePath);
        $this->assertSame([], $document->getEmbeddedMetaData(false));
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
    }

    /**
     * An open tag without close tag in a large file must not be buffered until the memory is exhausted
     */
    public function testOversizedXmpPacketIsAbortedCleanly(): void
    {
        $filePath = $this->createFileWithUnclosedXmpPacket(11 * 1024 * 1024);
        $document = new Document();

        try {
            $document->getXMPData($filePath);
            $this->fail('Expected an exception for an oversized XMP packet');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No close tag found within', $e->getMessage());
        }

        $document->handleEmbeddedMetaData(false, $filePath);
        $this->assertSame([], $document->getEmbeddedMetaData(false));
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
    }

    /**
     * The size limit of an XMP packet is enforced exactly: a packet whose close tag lies beyond the limit is rejected,
     * even if the close tag would be read with the next chunk, while a packet within the limit is read
     */
    public function testXmpPacketSizeLimitIsEnforcedExactly(): void
    {
        $limit = 10 * 1024 * 1024;
        $openTag = "<x:xmpmeta xmlns:x=\"adobe:ns:meta/\">\n";
        $closeTag = '</x:xmpmeta>';
        $document = new Document();

        // the close tag ends exactly at the limit
        $filePath = $this->createFileWithXmpPacket($openTag, $limit - strlen($openTag) - strlen($closeTag), $closeTag);
        $this->assertIsArray($document->getXMPData($filePath));

        // the close tag ends one byte beyond the limit
        $filePath = $this->createFileWithXmpPacket($openTag, $limit - strlen($openTag) - strlen($closeTag) + 1, $closeTag);

        try {
            $document->getXMPData($filePath);
            $this->fail('Expected an exception for an XMP packet exceeding the size limit');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No close tag found within', $e->getMessage());
        }
    }

    /**
     * Creates a file with an XMP packet consisting of the given open tag, the given amount of filler bytes and the
     * given close tag
     */
    private function createFileWithXmpPacket(string $openTag, int $fillerBytes, string $closeTag): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'pimcore-embedded-meta-data-test-');
        $this->tempFiles[] = $filePath;

        $handle = fopen($filePath, 'wb');
        fwrite($handle, "%PDF-1.4\n" . $openTag);
        $chunk = str_repeat('x', 1024 * 1024);
        for ($written = 0; $written < $fillerBytes; $written += strlen($chunk)) {
            fwrite($handle, substr($chunk, 0, min(strlen($chunk), $fillerBytes - $written)));
        }
        fwrite($handle, $closeTag . "\n%%EOF\n");
        fclose($handle);

        return $filePath;
    }

    public function testEmbeddedMetaDataIsOnlyExtractedOnceUnlessDataChanged(): void
    {
        $previousMetaData = ['title' => 'from a previous extraction'];

        $document = new Document();
        $document->setCustomSetting('embeddedMetaData', $previousMetaData);
        $document->setCustomSetting('embeddedMetaDataExtracted', true);

        $document->handleEmbeddedMetaData(false, $this->getFixturePath());
        $this->assertSame($previousMetaData, $document->getEmbeddedMetaData(false));

        $document->setDataChanged(true);
        $document->handleEmbeddedMetaData(false, $this->getFixturePath());
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool']);
    }
}
