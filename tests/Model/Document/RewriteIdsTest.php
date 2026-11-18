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

namespace Pimcore\Tests\Model\Document;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Link;
use Pimcore\Model\Document\Editable\Relation;
use Pimcore\Model\Document\Editable\Snippet;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.document.document
 */
class RewriteIdsTest extends ModelTestCase
{
    public function testSnippetDependenciesFollowRewrittenId(): void
    {
        $old = $this->createSnippet();
        $new = $this->createSnippet();

        try {
            $editable = new Snippet();
            $editable->setName('snippet');
            $editable->setSnippet($old);

            $editable->rewriteIds(['document' => [$old->getId() => $new->getId()]]);

            $this->assertSame($new->getId(), $editable->getId());
            // editmode reads the loaded snippet directly, so it has to be the new one right after the rewrite
            $this->assertSame(
                ['id' => $new->getId(), 'path' => $new->getRealFullPath()],
                $editable->getDataEditmode()
            );
            $this->assertSame(['document_' . $new->getId()], array_keys($editable->resolveDependencies()));
            $this->assertSame($new->getId(), $editable->getSnippet()?->getId());
        } finally {
            $old->delete();
            $new->delete();
        }
    }

    public function testRelationDependenciesFollowRewrittenId(): void
    {
        $old = TestHelper::createEmptyDocumentPage();
        $new = TestHelper::createEmptyDocumentPage();

        try {
            $editable = new Relation();
            $editable->setName('relation');
            $editable->setDataFromEditmode(['id' => $old->getId(), 'type' => 'document', 'subtype' => 'page']);

            $editable->rewriteIds(['document' => [$old->getId() => $new->getId()]]);

            $this->assertSame($new->getId(), $editable->getId());
            $this->assertSame(['document_' . $new->getId()], array_keys($editable->resolveDependencies()));
            $this->assertSame($new->getId(), $editable->getElement()?->getId());
        } finally {
            $old->delete();
            $new->delete();
        }
    }

    public function testLinkFullPathFollowsRewrittenId(): void
    {
        $old = TestHelper::createEmptyDocumentPage();
        $new = TestHelper::createEmptyDocumentPage();

        try {
            // fullPath is what Pimcore Studio stores and sends back as the link target on save
            $editable = new Link();
            $editable->setName('link');
            $editable->setDataFromResource(serialize([
                'linktype' => 'internal',
                'internal' => true,
                'internalType' => 'document',
                'internalId' => $old->getId(),
                'path' => $old->getRealFullPath(),
                'fullPath' => $old->getRealFullPath(),
            ]));

            $editable->rewriteIds(['document' => [$old->getId() => $new->getId()]]);

            $data = $editable->getData();
            $this->assertSame($new->getId(), $data['internalId']);
            $this->assertSame($new->getRealFullPath(), $data['fullPath']);
            $this->assertSame($new->getRealFullPath(), $editable->getDataEditmode()['fullPath']);
        } finally {
            $old->delete();
            $new->delete();
        }
    }

    public function testLinkFullPathIsRepairedInEditmode(): void
    {
        $old = TestHelper::createEmptyDocumentPage();
        $new = TestHelper::createEmptyDocumentPage();

        try {
            // a link copied before the fix: internalId already points to the copy, fullPath is still the old one
            $editable = new Link();
            $editable->setName('link');
            $editable->setDataFromResource(serialize([
                'linktype' => 'internal',
                'internal' => true,
                'internalType' => 'document',
                'internalId' => $new->getId(),
                'path' => $old->getRealFullPath(),
                'fullPath' => $old->getRealFullPath(),
            ]));

            $data = $editable->getDataEditmode();

            $this->assertSame($new->getId(), $data['internalId']);
            $this->assertSame($new->getRealFullPath(), $data['fullPath']);
        } finally {
            $old->delete();
            $new->delete();
        }
    }

    public function testContentMainDocumentFollowsRewrittenId(): void
    {
        $oldMain = TestHelper::createEmptyDocumentPage();
        $newMain = TestHelper::createEmptyDocumentPage();
        $page = TestHelper::createEmptyDocumentPage();

        try {
            $page->setContentMainDocumentId($oldMain->getId());

            Document\Service::rewriteIds($page, ['document' => [$oldMain->getId() => $newMain->getId()]]);

            $this->assertSame($newMain->getId(), $page->getContentMainDocumentId());
        } finally {
            $page->delete();
            $oldMain->delete();
            $newMain->delete();
        }
    }

    /**
     * Pimcore Studio starts the rewrite with empty parameters, also for an inheritance paste;
     * other callers pass enableInheritance.
     */
    public function inheritancePasteParamsProvider(): array
    {
        return [
            'empty parameters' => [[]],
            'enableInheritance' => [['enableInheritance' => true]],
        ];
    }

    /**
     * @dataProvider inheritancePasteParamsProvider
     */
    public function testContentMainDocumentIsKeptWhenCopyInheritsFromItsSource(array $params): void
    {
        $source = TestHelper::createEmptyDocumentPage();
        $copy = TestHelper::createEmptyDocumentPage();

        try {
            // an inheritance paste: the copy inherits from its source and the mapping is source => copy
            $copy->setContentMainDocumentId($source->getId());
            $copy->setProperty('target', 'document', $source);

            Document\Service::rewriteIds($copy, ['document' => [$source->getId() => $copy->getId()]], $params);

            $this->assertSame($source->getId(), $copy->getContentMainDocumentId());
            // the rewrite must not abort, properties are still rewritten
            $this->assertSame($copy->getId(), $copy->getProperty('target')?->getId());
        } finally {
            $copy->delete();
            $source->delete();
        }
    }

    /**
     * "Replace assignments" only requires source and target to be documents, so the target
     * does not have to be a page or snippet.
     */
    public function testContentMainDocumentIsKeptWhenTargetCannotBeInheritedFrom(): void
    {
        $main = TestHelper::createEmptyDocumentPage();
        $target = TestHelper::createDocumentFolder();
        $page = TestHelper::createEmptyDocumentPage();

        try {
            $page->setContentMainDocumentId($main->getId());
            $page->setProperty('target', 'document', $main);

            Document\Service::rewriteIds($page, ['document' => [$main->getId() => $target->getId()]]);

            $this->assertSame($main->getId(), $page->getContentMainDocumentId());
            // the rest of the rewrite still runs
            $this->assertSame($target->getId(), $page->getProperty('target')?->getId());
        } finally {
            $page->delete();
            $main->delete();
            $target->delete();
        }
    }

    public function testContentMainDocumentIsKeptWhenTargetInheritsFromTheDocument(): void
    {
        $main = TestHelper::createEmptyDocumentPage();
        $between = TestHelper::createEmptyDocumentPage();
        $target = TestHelper::createEmptyDocumentPage();
        $page = TestHelper::createEmptyDocumentPage();

        try {
            // target -> between -> page: switching page to target would close the chain
            $page->setContentMainDocumentId($main->getId());
            $page->setProperty('target', 'document', $main);
            $page->save();
            $between->setContentMainDocumentId($page->getId());
            $between->save();
            $target->setContentMainDocumentId($between->getId());
            $target->save();

            Document\Service::rewriteIds($page, ['document' => [$main->getId() => $target->getId()]]);

            $this->assertSame($main->getId(), $page->getContentMainDocumentId());
            // the rest of the rewrite still runs
            $this->assertSame($target->getId(), $page->getProperty('target')?->getId());
        } finally {
            foreach ([$target, $between, $page] as $document) {
                $document->setContentMainDocumentId(null);
                $document->save();
            }
            $target->delete();
            $between->delete();
            $page->delete();
            $main->delete();
        }
    }

    private function createSnippet(): Document\Snippet
    {
        /** @var Document\Snippet $snippet */
        $snippet = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Model\\Document\\Snippet');

        return $snippet;
    }
}
