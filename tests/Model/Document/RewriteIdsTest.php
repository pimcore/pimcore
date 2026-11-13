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

    private function createSnippet(): Document\Snippet
    {
        /** @var Document\Snippet $snippet */
        $snippet = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Model\\Document\\Snippet');

        return $snippet;
    }
}
