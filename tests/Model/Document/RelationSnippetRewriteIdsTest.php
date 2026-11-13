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
use Pimcore\Model\Document\Editable\Relation;
use Pimcore\Model\Document\Editable\Snippet;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.document.document
 */
class RelationSnippetRewriteIdsTest extends ModelTestCase
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

    private function createSnippet(): Document\Snippet
    {
        /** @var Document\Snippet $snippet */
        $snippet = TestHelper::createEmptyDocument('', true, true, '\\Pimcore\\Model\\Document\\Snippet');

        return $snippet;
    }
}
