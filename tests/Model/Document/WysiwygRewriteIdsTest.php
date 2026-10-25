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

use Pimcore\Model\Document\Editable\Wysiwyg;
use Pimcore\Model\Document\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.document.document
 */
class WysiwygRewriteIdsTest extends ModelTestCase
{
    private const TEXT_WITH_ENTITIES = '<p>a &amp; b&nbsp;c &lt;x&gt; ü €</p>';

    // the sanitizer on save stores <br /> and &#34;, the DOM round trip of rewriteIds() would turn them into <br> and "
    private const TEXT_WITH_BREAK_AND_QUOTES = '<p>line<br>break "quoted"</p>';

    public function testRewriteIdsKeepsTextWithoutMappedIds(): void
    {
        $editable = $this->createWysiwyg(self::TEXT_WITH_ENTITIES);

        $editable->rewriteIds(['document' => [999999 => 999998]]);

        $this->assertSame(self::TEXT_WITH_ENTITIES, $editable->getData());
    }

    public function testRewriteIdsKeepsTextWithUnmappedLink(): void
    {
        $text = '<p>x&nbsp;<a href="/a" pimcore_id="5" pimcore_type="document">link</a></p>';
        $editable = $this->createWysiwyg($text);

        $editable->rewriteIds(['document' => [999999 => 999998]]);

        $this->assertSame($text, $editable->getData());
    }

    public function testRewriteIdsRewritesMappedId(): void
    {
        $editable = $this->createWysiwyg('<p><a href="/a" pimcore_id="5" pimcore_type="document">link</a></p>');

        $editable->rewriteIds(['document' => [5 => 7]]);

        $this->assertStringContainsString('pimcore_id="7"', $editable->getData());
    }

    public function testServiceRewriteIdsKeepsInheritedWysiwygUnchanged(): void
    {
        $main = TestHelper::createEmptyDocumentPage();
        $main->setEditable($this->createWysiwyg(self::TEXT_WITH_BREAK_AND_QUOTES));
        $main->save();

        $copy = TestHelper::createEmptyDocumentPage('copy_');
        $copy->setContentMainDocumentId($main->getId(), true);
        $copy->setEditables([]);

        try {
            Service::rewriteIds($copy, ['document' => [999999 => 999998]], ['enableInheritance' => true]);

            // the rewritten editable equals the main document's, so no own editable is stored and the copy keeps inheriting
            $this->assertSame([], $copy->getEditables());
        } finally {
            $copy->delete();
            $main->delete();
        }
    }

    private function createWysiwyg(string $text): Wysiwyg
    {
        $editable = new Wysiwyg();
        $editable->setName('content');
        $editable->setDataFromEditmode($text);

        return $editable;
    }
}
