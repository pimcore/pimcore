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

namespace Pimcore\Tests\Unit\Model\DataObject;

use Pimcore\Model\DataObject\Traits\LocateFileTrait;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Regression: locateDefinitionFile()/locateFile() used to re-resolve the candidate file with
 * realpath() and reject it unless the resolved target still lived under the base directory.
 * That rejected any definition or generated PHP class file that was a symlink pointing outside
 * PIMCORE_CLASS_DEFINITION_DIRECTORY/PIMCORE_CLASS_DIRECTORY (e.g. one shipped by a composer
 * package or shared from another checkout), even though the $key is already restricted to a
 * single path segment (no "/", "\" or "..") a few lines above, which is what actually prevents
 * escaping the base directory.
 */
class LocateFileTraitTest extends TestCase
{
    private Filesystem $filesystem;

    private string $externalTarget;

    private array $createdSymlinks = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->externalTarget = sys_get_temp_dir() . '/locate-file-trait-test-target-' . uniqid() . '.php';
        $this->filesystem->dumpFile($this->externalTarget, '<?php return "external";');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(array_merge($this->createdSymlinks, [$this->externalTarget]));

        parent::tearDown();
    }

    public function testLocateDefinitionFileFollowsSymlinkOutsideDefaultDirectory(): void
    {
        $symlink = $this->symlinkInto(PIMCORE_CLASS_DEFINITION_DIRECTORY, 'fieldcollections', 'SymlinkedDefinition');

        $this->assertSame(
            $symlink,
            $this->createLocator()->definitionFile('SymlinkedDefinition', 'fieldcollections/%s.php')
        );
    }

    public function testLocateFileFollowsSymlinkOutsideDefaultDirectory(): void
    {
        $symlink = $this->symlinkInto(PIMCORE_CLASS_DIRECTORY, 'fieldcollections', 'SymlinkedClass');

        $this->assertSame(
            $symlink,
            $this->createLocator()->file('SymlinkedClass', 'fieldcollections/%s.php')
        );
    }

    public function testLocateDefinitionFileFollowsSymlinkInCustomDirectory(): void
    {
        $this->filesystem->mkdir(PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY);
        $symlink = $this->symlinkInto(PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY, 'fieldcollections', 'SymlinkedCustomDefinition');

        $this->assertSame(
            $symlink,
            $this->createLocator()->definitionFile('SymlinkedCustomDefinition', 'fieldcollections/%s.php')
        );
    }

    public function testLocateFileFollowsSymlinkInCustomDirectory(): void
    {
        $this->filesystem->mkdir(PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY);
        $symlink = $this->symlinkInto(PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY, 'fieldcollections', 'SymlinkedCustomClass');

        $this->assertSame(
            $symlink,
            $this->createLocator()->file('SymlinkedCustomClass', 'fieldcollections/%s.php')
        );
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testInvalidKeysAreStillRejected(string $key): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid key');

        $this->createLocator()->definitionFile($key, 'fieldcollections/%s.php');
    }

    public function invalidKeyProvider(): array
    {
        return [
            'path separator' => ['foo/bar'],
            'backslash' => ['foo\\bar'],
            'parent traversal' => ['../bar'],
        ];
    }

    private function symlinkInto(string $baseDirectory, string $subDirectory, string $key): string
    {
        $directory = $baseDirectory . '/' . $subDirectory;
        $this->filesystem->mkdir($directory);

        // Mirror the trait's own realpath(base) resolution: the constant may contain
        // unresolved ".." segments (e.g. PIMCORE_PROJECT_ROOT . '/tests/..') that the trait
        // canonicalizes away before appending $key, so the assertion must do the same.
        $symlink = realpath($directory) . '/' . $key . '.php';
        $this->filesystem->remove($symlink);
        $this->filesystem->symlink($this->externalTarget, $symlink);
        $this->createdSymlinks[] = $symlink;

        return $symlink;
    }

    private function createLocator(): object
    {
        return new class {
            use LocateFileTrait;

            public function definitionFile(string $key, string $pathTemplate): string
            {
                return $this->locateDefinitionFile($key, $pathTemplate);
            }

            public function file(string $key, string $pathTemplate): string
            {
                return $this->locateFile($key, $pathTemplate);
            }
        };
    }
}
