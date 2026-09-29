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

namespace Pimcore\Tests\Service\Element;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\DatabaseVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\DelegateVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\ElementDelegateVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Asset and object versions go to one storage ("remote"), document versions to the default storage ("local").
 */
class ElementDelegateVersionStorageTest extends TestCase
{
    private FilesystemOperator $remoteStorage;

    private FilesystemOperator $localStorage;

    protected function setUp(): void
    {
        parent::setUp();

        // needed for tests with DatabaseVersionStorageAdapter (same as in VersionTest)
        Db::get()->executeStatement("CREATE TABLE IF NOT EXISTS `versionsData` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `cid` int(11) unsigned DEFAULT NULL,
                                  `ctype` enum('document','asset','object') DEFAULT NULL,
                                  `metaData` longblob DEFAULT NULL,
                                  `binaryData` longblob DEFAULT NULL,
                                  PRIMARY KEY (`id`)
                                ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci");

        $this->remoteStorage = new Filesystem(new InMemoryFilesystemAdapter());
        $this->localStorage = new Filesystem(new InMemoryFilesystemAdapter());

        $remote = new FileSystemVersionStorageAdapter($this->remoteStorage);
        $this->setStorageAdapter(new ElementDelegateVersionStorageAdapter(
            ['asset' => $remote, 'object' => $remote],
            new FileSystemVersionStorageAdapter($this->localStorage),
        ));
    }

    protected function tearDown(): void
    {
        $this->setStorageAdapter(new FileSystemVersionStorageAdapter());
        Db::get()->executeStatement('DROP TABLE IF EXISTS versionsData');

        parent::tearDown();
    }

    public function testStorageTypeOfNestedDelegateIsRecorded(): void
    {
        // objects: size-based delegate (small data in the database, larger on the remote storage)
        $this->setStorageAdapter(new ElementDelegateVersionStorageAdapter(
            ['object' => new DelegateVersionStorageAdapter(
                1000000,
                new DatabaseVersionStorageAdapter(Db::get()),
                new FileSystemVersionStorageAdapter($this->remoteStorage)
            )],
            new FileSystemVersionStorageAdapter($this->localStorage),
        ));

        $object = TestHelper::createEmptyObject();
        $document = TestHelper::createEmptyDocumentPage();

        $objectVersion = $this->loadLatestVersion($object, 'object');
        $this->assertSame('db', $objectVersion->getStorageType(), 'the storage type of the routed adapter is recorded');
        $this->assertNotNull($objectVersion->loadData());

        $documentVersion = $this->loadLatestVersion($document, 'document');
        $this->assertSame('fs', $documentVersion->getStorageType());
        $this->assertNotNull($documentVersion->loadData());
    }

    private function setStorageAdapter(VersionStorageAdapterInterface $adapter): void
    {
        Pimcore::getContainer()->get(VersionStorageAdapterInterface::class)->setStorageAdapter($adapter);
    }

    private function loadLatestVersion(ElementInterface $element, string $elementType): Version
    {
        $listing = new Version\Listing();
        $listing->setCondition('cid = ? AND ctype = ?', [$element->getId(), $elementType]);
        $listing->setOrderKey('id')->setOrder('DESC')->setLimit(1);
        $versions = $listing->load();
        $this->assertCount(1, $versions, 'the element has a version');

        return $versions[0];
    }
}
