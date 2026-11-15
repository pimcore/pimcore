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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\DefinitionFileCache;

class DefinitionFileCacheTest extends TestCase
{
    private string $definitionFile;

    protected function setUp(): void
    {
        $this->definitionFile = tempnam(sys_get_temp_dir(), 'definition_') . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->definitionFile);
        DefinitionFileCache::clear();
    }

    private function writeDefinition(string $name): void
    {
        file_put_contents(
            $this->definitionFile,
            '<?php return \Pimcore\Model\DataObject\ClassDefinition::create([\'name\' => \'' . $name . '\']);'
        );
    }

    public function testIncludedDefinitionIsCached(): void
    {
        $this->writeDefinition('CachedDefinition');

        $first = DefinitionFileCache::load($this->definitionFile);
        $second = DefinitionFileCache::load($this->definitionFile);

        $this->assertInstanceOf(ClassDefinition::class, $first);
        $this->assertSame('CachedDefinition', $first->getName());
        $this->assertSame($first, $second);
    }

    public function testChangedFileIsReloaded(): void
    {
        $this->writeDefinition('OriginalDefinition');
        $original = DefinitionFileCache::load($this->definitionFile);

        $this->writeDefinition('ChangedDefinition');
        touch($this->definitionFile, time() + 2);

        $reloaded = DefinitionFileCache::load($this->definitionFile);

        $this->assertNotSame($original, $reloaded);
        $this->assertSame('ChangedDefinition', $reloaded->getName());
    }

    public function testForceBypassesCache(): void
    {
        $this->writeDefinition('ForcedDefinition');

        $cached = DefinitionFileCache::load($this->definitionFile);
        $forced = DefinitionFileCache::load($this->definitionFile, true);

        $this->assertNotSame($cached, $forced);
    }

    public function testClearInvalidatesEntry(): void
    {
        $this->writeDefinition('ClearedDefinition');

        $first = DefinitionFileCache::load($this->definitionFile);
        DefinitionFileCache::clear($this->definitionFile);
        $second = DefinitionFileCache::load($this->definitionFile);

        $this->assertNotSame($first, $second);
    }

    public function testMissingFileReturnsNull(): void
    {
        $this->assertNull(DefinitionFileCache::load($this->definitionFile . '.missing'));
    }

    public function testDeletedFileIsEvicted(): void
    {
        $this->writeDefinition('DeletedDefinition');
        $this->assertInstanceOf(ClassDefinition::class, DefinitionFileCache::load($this->definitionFile));

        unlink($this->definitionFile);

        $this->assertNull(DefinitionFileCache::load($this->definitionFile));
    }
}
