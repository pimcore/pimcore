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

namespace Pimcore\Tests\Unit\Document;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Document\Renderer\DocumentRendererInterface;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Site;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Lock\LockFactory;

class StaticPageGeneratorTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $documentsConfig = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentsConfig = Config::getSystemConfiguration('documents');

        $config = $this->documentsConfig ?? [];
        $config['static_page_generator']['use_main_domain'] = false;
        Config::setSystemConfiguration($config, 'documents');

        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        Config::setSystemConfiguration($this->documentsConfig, 'documents');
        RuntimeCache::clear();

        parent::tearDown();
    }

    public function testPrettyUrlCollisionAcrossSitesIsDisambiguatedBySite(): void
    {
        $this->registerSiteMapping(['/siteA' => 1, '/siteB' => 2]);

        $documentA = $this->makePage('/siteA/en/products-real-path', '/products');
        $documentB = $this->makePage('/siteB/en/products-real-path', '/products');

        $generator = $this->makeGenerator();

        $pathA = $generator->getStoragePath($documentA);
        $pathB = $generator->getStoragePath($documentB);

        $this->assertNotSame($pathA, $pathB, 'Two sites sharing a pretty URL must not resolve to the same storage path');
        $this->assertSame('/%site_1/products.html', $pathA);
        $this->assertSame('/%site_2/products.html', $pathB);
    }

    public function testPrettyUrlStoragePathIsUnchangedWhenDocumentIsNotPartOfAnySite(): void
    {
        $this->registerSiteMapping([]);

        $document = $this->makePage('/en/products-real-path', '/products');

        $generator = $this->makeGenerator();

        $this->assertSame('/products.html', $generator->getStoragePath($document));
    }

    public function testRealFullPathStoragePathIsUnaffectedBySiteMapping(): void
    {
        $this->registerSiteMapping(['/siteA' => 1]);

        $document = $this->makePage('/siteA/products', null);

        $generator = $this->makeGenerator();

        $this->assertSame('/siteA/products.html', $generator->getStoragePath($document));
    }

    /**
     * @param array<string, int> $siteMapping
     */
    private function registerSiteMapping(array $siteMapping): void
    {
        RuntimeCache::set('sites_path_mapping', $siteMapping);

        foreach ($siteMapping as $id) {
            $site = new Site();
            $site->setId($id);
            RuntimeCache::set('site_id_' . $id, $site);
        }
    }

    private function makePage(string $realFullPath, ?string $prettyUrl): Page
    {
        $page = $this->createMock(Page::class);
        $page->method('getRealFullPath')->willReturn($realFullPath);
        $page->method('getPrettyUrl')->willReturn($prettyUrl);

        return $page;
    }

    private function makeGenerator(): StaticPageGenerator
    {
        return new StaticPageGenerator(
            $this->createMock(DocumentRendererInterface::class),
            $this->createMock(LockFactory::class),
            $this->createMock(SystemSettingsConfig::class)
        );
    }
}
