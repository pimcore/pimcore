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
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

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
}
