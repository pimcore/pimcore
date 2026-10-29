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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Regression test: saving a field collection / object brick definition must invalidate the opcache entry of its
 * definition file, otherwise the next include serves the previous definition until opcache revalidates.
 *
 * Needs opcache in CLI (opcache.enable_cli=1), skipped otherwise.
 *
 * @group model.dataobject.definition
 */
class FieldcollectionObjectbrickDefinitionFileOpcacheTest extends ModelTestCase
{
    protected function setUp(): void
    {
        if (!function_exists('opcache_get_status') || !(opcache_get_status(false)['opcache_enabled'] ?? false)) {
            $this->markTestSkipped('Needs opcache.enable_cli=1.');
        }

        parent::setUp();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_Unittest();
        $this->tester->setupPimcoreClass_Inheritance();
        $this->tester->setupFieldcollection_Unittestfieldcollection();
        $this->tester->setupObjectbrick_UnittestBrick();
    }

    public function testFieldcollectionDefinitionSaveInvalidatesOpcache(): void
    {
        $this->assertSaveInvalidatesOpcache(Fieldcollection\Definition::getByKey('unittestfieldcollection'));
    }

    public function testObjectbrickDefinitionSaveInvalidatesOpcache(): void
    {
        $this->assertSaveInvalidatesOpcache(Objectbrick\Definition::getByKey('unittestBrick'));
    }

    private function assertSaveInvalidatesOpcache(?Fieldcollection\Definition $definition): void
    {
        $this->assertNotNull($definition);

        $file = $definition->getDefinitionFile();
        $originalGroup = $definition->getGroup();

        // opcache refuses to cache files younger than opcache.file_update_protection
        touch($file, time() - 60);
        clearstatcache(true, $file);
        $this->assertTrue(opcache_compile_file($file));
        $this->assertTrue(opcache_is_script_cached($file));

        try {
            $definition->setGroup('opcacheTestGroup');
            $definition->save();

            $reloaded = include $file;
            $this->assertInstanceOf(get_class($definition), $reloaded);
            $this->assertSame('opcacheTestGroup', $reloaded->getGroup());
        } finally {
            $definition->setGroup($originalGroup);
            $definition->save();
        }
    }
}
