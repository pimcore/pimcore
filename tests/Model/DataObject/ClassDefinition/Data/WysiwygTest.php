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

namespace Pimcore\Tests\Model\DataObject\ClassDefinition\Data;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Wysiwyg;
use Pimcore\Model\DataObject\ClassDefinition\Helper\WysiwygPersistingScope;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\DataObject\UnittestWysiwygBlock;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use RuntimeException;

class WysiwygTest extends ModelTestCase
{
    protected function needsDb(): bool
    {
        return true;
    }

    public function testGetVersionPreviewStripsBrokenLinkWithExtraAttributes(): void
    {
        RuntimeCache::clear();

        $unpublishedDocument = new Document\Page();
        $unpublishedDocument->setKey('unpublished-version-preview-testing');
        $unpublishedDocument->setPublished(false);
        $unpublishedDocument->setParentId(1);
        $unpublishedDocument->setUserOwner(1);
        $unpublishedDocument->setUserModification(1);
        $unpublishedDocument->setCreationDate(time());
        $unpublishedDocument->save();

        $data = sprintf(
            'Link to a document <a href="/some/path" target="_blank" pimcore_id="%s" pimcore_type="document" rel="noopener">Link text</a>',
            $unpublishedDocument->getId()
        );

        $field = new Wysiwyg();
        $field->setName('description');

        $this->assertEquals('Link to a document Link text', $field->getVersionPreview($data));
    }

    protected function tearDown(): void
    {
        Pimcore::unsetAdminMode();

        parent::tearDown();
    }

    public function testLinksToUnpublishedElementsSurviveSaveAndReload(): void
    {
        RuntimeCache::clear();
        Pimcore::setAdminMode();

        $unpublishedObject = TestHelper::createEmptyObject('wysiwyg-unpublished-target-', true, false);
        $publishedObject = TestHelper::createEmptyObject('wysiwyg-published-target-');
        $unpublishedDocument = new Document\Page();
        $unpublishedDocument->setKey('unpublished-persistence-testing');
        $unpublishedDocument->setPublished(false);
        $unpublishedDocument->setParentId(1);
        $unpublishedDocument->setUserOwner(1);
        $unpublishedDocument->setUserModification(1);
        $unpublishedDocument->setCreationDate(time());
        $unpublishedDocument->save();

        $html = sprintf(
            '<p><a href="/x" pimcore_id="%d" pimcore_type="object">unpublished object</a>'
            . '<a href="/x" pimcore_id="%d" pimcore_type="document">unpublished document</a>'
            . '<a href="/x" pimcore_id="%d" pimcore_type="object">published object</a></p>',
            $unpublishedObject->getId(),
            $unpublishedDocument->getId(),
            $publishedObject->getId()
        );

        $source = TestHelper::createEmptyObject('wysiwyg-source-', false);
        $source->setWysiwyg($html);
        $source->setLwysiwyg($html, 'en');
        $source->save();

        RuntimeCache::clear();
        $reloaded = DataObject::getById($source->getId(), ['force' => true]);
        $this->assertInstanceOf(Unittest::class, $reloaded);

        $definitions = $reloaded->getClass()->getFieldDefinitions();
        $localizedDefinitions = $definitions['localizedfields']->getFieldDefinitions();
        $values = [
            [$definitions['wysiwyg'], $reloaded->getObjectVar('wysiwyg')],
            [
                $localizedDefinitions['lwysiwyg'],
                $reloaded->getLocalizedfields()->getLocalizedValue('lwysiwyg', 'en', true),
            ],
        ];

        foreach ($values as [$definition, $raw]) {
            // stored value keeps the tags of the unpublished targets
            $this->assertStringContainsString(
                sprintf('pimcore_id="%d" pimcore_type="object">unpublished object', $unpublishedObject->getId()),
                $raw
            );
            $this->assertStringContainsString(
                sprintf('pimcore_id="%d" pimcore_type="document">unpublished document', $unpublishedDocument->getId()),
                $raw
            );
            // so does the link to a published object whose class has no link generator
            $this->assertStringContainsString(
                sprintf('pimcore_id="%d" pimcore_type="object">published object', $publishedObject->getId()),
                $raw
            );

            // editor view keeps them too
            $this->assertEquals($raw, $definition->getDataForEditmode($raw, $reloaded));
        }

        // the editor getter (admin mode) keeps them, the frontend getter reduces them to their text
        $this->assertStringContainsString(
            sprintf('pimcore_id="%d" pimcore_type="object"', $unpublishedObject->getId()),
            $reloaded->getWysiwyg()
        );
        Pimcore::unsetAdminMode();
        $frontend = $reloaded->getWysiwyg();
        $this->assertStringContainsString('unpublished object', $frontend);
        $this->assertStringNotContainsString(
            sprintf('pimcore_id="%d" pimcore_type="object"', $unpublishedObject->getId()),
            $frontend
        );
        $this->assertStringNotContainsString(
            sprintf('pimcore_id="%d" pimcore_type="document"', $unpublishedDocument->getId()),
            $frontend
        );

        // saving the reloaded object again in the editor context does not lose the links
        Pimcore::setAdminMode();
        $reloaded->setKey($reloaded->getKey() . '-resaved');
        $reloaded->save();
        RuntimeCache::clear();
        $reloaded = DataObject::getById($source->getId(), ['force' => true]);
        $this->assertStringContainsString(
            sprintf('pimcore_id="%d"', $unpublishedObject->getId()),
            $reloaded->getObjectVar('wysiwyg')
        );
    }

    /**
     * Saves outside a Studio request (grid batch edit job, scheduled publishing, import) run without admin
     * mode and must not lose the links either.
     */
    public function testLinksToUnpublishedElementsSurviveSaveWithoutAdminMode(): void
    {
        RuntimeCache::clear();
        Pimcore::unsetAdminMode();

        $unpublishedObject = TestHelper::createEmptyObject('wysiwyg-nonadmin-target-', true, false);
        $html = sprintf(
            '<p><a href="/x" pimcore_id="%d" pimcore_type="object">unpublished object</a></p>',
            $unpublishedObject->getId()
        );
        $expected = sprintf('pimcore_id="%d" pimcore_type="object">unpublished object', $unpublishedObject->getId());

        // a new object, as created by an import
        $source = TestHelper::createEmptyObject('wysiwyg-nonadmin-source-', false);
        $source->setWysiwyg($html);
        $source->setLwysiwyg($html, 'en');
        $source->save();
        $this->assertStoredLinks($source->getId(), $expected);

        // an existing object loaded and saved again, as in a batch edit of another field
        RuntimeCache::clear();
        $reloaded = DataObject::getById($source->getId(), ['force' => true]);
        $reloaded->setInput('edited');
        $reloaded->save();
        $this->assertStoredLinks($source->getId(), $expected);

        // the frontend view still reduces the link to its text, saving does not switch that off
        RuntimeCache::clear();
        $reloaded = DataObject::getById($source->getId(), ['force' => true]);
        $this->assertStringNotContainsString('pimcore_id', $reloaded->getWysiwyg());
        $this->assertStringContainsString('unpublished object', $reloaded->getWysiwyg());
    }

    public function testLinksToUnpublishedElementsInBlockSurviveSaveWithoutAdminMode(): void
    {
        RuntimeCache::clear();
        Pimcore::unsetAdminMode();
        $this->tester->setupPimcoreClass_WysiwygBlock();

        $unpublishedObject = TestHelper::createEmptyObject('wysiwyg-block-target-', true, false);
        $html = sprintf(
            '<p><a href="/x" pimcore_id="%d" pimcore_type="object">unpublished object</a></p>',
            $unpublishedObject->getId()
        );
        $expected = sprintf('pimcore_id="%d" pimcore_type="object">unpublished object', $unpublishedObject->getId());

        $object = new UnittestWysiwygBlock();
        $object->setParent(DataObject\Service::createFolderByPath('/wysiwyg-block'));
        $object->setKey('wysiwyg-block-1');
        $object->setPublished(true);
        $object->setTestblock([['blockwysiwyg' => new BlockElement('blockwysiwyg', 'wysiwyg', $html)]]);
        $object->save();

        // load, save again without touching the block (a batch edit of another field), and read it like the editor
        foreach ([1, 2] as $ignored) {
            RuntimeCache::clear();
            $reloaded = DataObject::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(UnittestWysiwygBlock::class, $reloaded);
            $reloaded->save();
        }

        RuntimeCache::clear();
        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        Pimcore::setAdminMode();
        $this->assertStringContainsString($expected, $reloaded->getTestblock()[0]['blockwysiwyg']->getData());
    }

    /**
     * Copying runs in the messenger worker (Studio paste) or the CLI, without admin mode. Reading the source (which
     * writes block values back) and rewriting the ids must not drop the links in the copy.
     */
    public function testLinksToUnpublishedElementsSurviveCopyAndRewriteIdsWithoutAdminMode(): void
    {
        RuntimeCache::clear();
        Pimcore::unsetAdminMode();
        $this->tester->setupPimcoreClass_WysiwygBlock();

        $unpublishedObject = TestHelper::createEmptyObject('wysiwyg-copy-target-', true, false);
        $html = sprintf(
            '<p><a href="/x" pimcore_id="%d" pimcore_type="object">unpublished object</a></p>',
            $unpublishedObject->getId()
        );
        $expected = sprintf('pimcore_id="%d" pimcore_type="object">unpublished object', $unpublishedObject->getId());

        $folder = DataObject\Service::createFolderByPath('/wysiwyg-copy');
        $object = new UnittestWysiwygBlock();
        $object->setParent($folder);
        $object->setKey('wysiwyg-copy-source');
        $object->setPublished(true);
        $object->setTestblock([['blockwysiwyg' => new BlockElement('blockwysiwyg', 'wysiwyg', $html)]]);
        $object->save();

        RuntimeCache::clear();
        $source = DataObject::getById($object->getId(), ['force' => true]);
        $copy = (new DataObject\Service())->copyAsChild($folder, $source);
        $this->assertInstanceOf(UnittestWysiwygBlock::class, $copy);

        // "update references" of a Studio paste
        RuntimeCache::clear();
        $copy = DataObject::getById($copy->getId(), ['force' => true]);
        DataObject\Service::rewriteIds($copy, ['object' => [999999 => 999998]]);
        $copy->save();

        foreach ([$source->getId(), $copy->getId()] as $id) {
            RuntimeCache::clear();
            $reloaded = DataObject::getById($id, ['force' => true]);
            Pimcore::setAdminMode();
            $this->assertStringContainsString($expected, $reloaded->getTestblock()[0]['blockwysiwyg']->getData());
            Pimcore::unsetAdminMode();
        }

        // the same for a plain wysiwyg field
        $plain = TestHelper::createEmptyObject('wysiwyg-copy-plain-', false);
        $plain->setWysiwyg($html);
        $plain->setLwysiwyg($html, 'en');
        $plain->save();
        RuntimeCache::clear();
        $plain = DataObject::getById($plain->getId(), ['force' => true]);
        DataObject\Service::rewriteIds($plain, ['object' => [999999 => 999998]]);
        $plain->save();
        $this->assertStoredLinks($plain->getId(), $expected);
    }

    public function testPersistingScopeIsRestoredAfterFailure(): void
    {
        $this->assertFalse(WysiwygPersistingScope::isActive());

        try {
            WysiwygPersistingScope::run(function (): void {
                $this->assertTrue(WysiwygPersistingScope::isActive());
                WysiwygPersistingScope::run(fn () => null);
                $this->assertTrue(WysiwygPersistingScope::isActive());

                throw new RuntimeException('failed save');
            });
            $this->fail('The exception must pass through the scope');
        } catch (RuntimeException) {
            $this->assertFalse(WysiwygPersistingScope::isActive());
        }
    }

    private function assertStoredLinks(int $id, string $expected): void
    {
        RuntimeCache::clear();
        $object = DataObject::getById($id, ['force' => true]);
        $this->assertInstanceOf(Unittest::class, $object);

        $this->assertStringContainsString($expected, $object->getObjectVar('wysiwyg'));

        // the localized getter reduces the link in the frontend, so read it the way the editor does
        Pimcore::setAdminMode();
        $this->assertStringContainsString(
            $expected,
            $object->getLocalizedfields()->getLocalizedValue('lwysiwyg', 'en', true)
        );
        Pimcore::unsetAdminMode();
    }
}
