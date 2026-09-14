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

namespace Pimcore\Tests\Unit\Helper;

use Pimcore\Helper\ReservedWordsHelper;
use Pimcore\Tests\Support\Test\TestCase;

class ReservedWordsHelperTest extends TestCase
{
    private ReservedWordsHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = new ReservedWordsHelper();
    }

    /**
     * A generated DataObject class is emitted into the `Pimcore\Model\DataObject` namespace, so a
     * class named after anything already living there shadows it: the application's PSR-4 prefix
     * `Pimcore\Model\DataObject\` => `var/classes/DataObject` is longer than the core's
     * `Pimcore\Model\` => `models`, so the generated file wins and the core class never loads.
     *
     * Derived from the directory rather than hard-coded, so a class added to the namespace later
     * fails this test instead of silently becoming usable as a DataObject class name.
     */
    public function testEveryClassInTheDataObjectNamespaceRootIsReservedAsClassName(): void
    {
        $notReserved = [];

        foreach ($this->dataObjectNamespaceRootClassNames() as $name) {
            if (!$this->helper->isReservedDataObjectClassName($name)) {
                $notReserved[] = $name;
            }
        }

        $this->assertSame(
            [],
            $notReserved,
            'Add these to ReservedWordsHelper::PIMCORE_DATA_OBJECT_CLASSES (lowercased): '
            . implode(', ', $notReserved)
        );
    }

    public function testIsReservedDataObjectClassNameIsCaseInsensitive(): void
    {
        $this->assertTrue($this->helper->isReservedDataObjectClassName('service'));
        $this->assertTrue($this->helper->isReservedDataObjectClassName('Service'));
        $this->assertTrue($this->helper->isReservedDataObjectClassName('SERVICE'));
    }

    public function testIsReservedDataObjectClassNameStillCoversPhpAndPimcoreWords(): void
    {
        $this->assertTrue($this->helper->isReservedDataObjectClassName('var'));
        $this->assertTrue($this->helper->isReservedDataObjectClassName('Folder'));
        $this->assertTrue($this->helper->isReservedDataObjectClassName('Concrete'));
    }

    public function testUnrelatedNameIsNotReserved(): void
    {
        $this->assertFalse($this->helper->isReservedDataObjectClassName('Product'));
        $this->assertFalse($this->helper->isReservedDataObjectClassName('NewsArticle'));
    }

    /**
     * Select options are generated into the `Pimcore\Model\DataObject\SelectOptions` sub-namespace
     * and therefore cannot collide with those classes. Tightening the shared list would reject
     * existing select-options configurations named e.g. `Service` on their next save.
     *
     * @see \Pimcore\Model\DataObject\SelectOptions\Config::setId()
     */
    public function testDataObjectClassNamesAreNotAddedToTheSharedReservedWordList(): void
    {
        $sharedWords = $this->helper->getAllReservedWords();

        foreach (ReservedWordsHelper::PIMCORE_DATA_OBJECT_CLASSES as $name) {
            $this->assertNotContains(
                $name,
                $sharedWords,
                sprintf('`%s` must stay usable as a select options ID', $name)
            );
        }

        $this->assertFalse($this->helper->isReservedWord('Service'));
    }

    /**
     * @return string[]
     */
    private function dataObjectNamespaceRootClassNames(): array
    {
        $files = glob(PIMCORE_PATH . '/models/DataObject/*.php') ?: [];
        $this->assertNotEmpty($files, 'Could not read the DataObject namespace root');

        return array_map(static fn (string $file): string => basename($file, '.php'), $files);
    }
}
