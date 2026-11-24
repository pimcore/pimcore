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

namespace Pimcore\Tests\Unit\Document\Editable;

use Pimcore;
use Pimcore\Model\Document\Editable\Wysiwyg;
use Pimcore\Model\Document\Page;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Text;

/**
 * Regression test for GHSA-h5rr-9v6v-2r3p: the Wysiwyg editable only ran its HTML sanitizer in
 * save(), which is skipped for draft/autoSave versions. A draft could therefore keep raw HTML
 * (including <script>/event-handler payloads), which was then rendered unsanitized wherever the
 * value is read without having gone through save() - e.g. a stored draft version restored via
 * setDataFromResource(). frontend() and getDataEditmode() must now sanitize on read, independent
 * of whether the value was ever passed through save().
 *
 * @group unit.document.editable.wysiwyg
 */
class WysiwygDraftRenderSanitizationTest extends TestCase
{
    private const UNSAFE_HTML = '<p>ok</p><img src=x onerror=alert(1)><script>alert(2)</script>';

    // frontend()/getDataEditmode() resolve the owning document for link rewriting; attach an
    // in-memory, unsaved one so that lookup does not need the database.
    private function createEditable(mixed $data): Wysiwyg
    {
        $editable = new Wysiwyg();
        $editable->setName('body');
        $editable->setDocument(new Page());
        $editable->setDataFromResource($data);

        return $editable;
    }

    public function testFrontendSanitizesValueThatNeverWentThroughSave(): void
    {
        // simulates a draft version loaded back without ever calling save(), e.g. a version
        // stored via the "version"/"autoSave" task
        $editable = $this->createEditable(self::UNSAFE_HTML);

        $rendered = (string) $editable->frontend();

        $this->assertStringNotContainsString('onerror', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringContainsString('<p>ok</p>', $rendered);
    }

    public function testGetDataEditmodeSanitizesValueThatNeverWentThroughSave(): void
    {
        $editable = $this->createEditable(self::UNSAFE_HTML);

        $editmodeData = (string) $editable->getDataEditmode();

        $this->assertStringNotContainsString('onerror', $editmodeData);
        $this->assertStringNotContainsString('<script', $editmodeData);
        $this->assertStringContainsString('<p>ok</p>', $editmodeData);
    }

    public function testFrontendStillRendersLegitimateMarkup(): void
    {
        $editable = $this->createEditable('<p>Hello <b>world</b></p>');

        $this->assertSame('<p>Hello <b>world</b></p>', (string) $editable->frontend());
    }

    /**
     * A value stored by a regular save() is sanitized once more when it is rendered, so the shipped
     * sanitizer configuration has to return its own output unchanged - otherwise published content
     * would render differently from what was saved.
     *
     * @dataProvider storedMarkupProvider
     */
    public function testRenderingDoesNotChangeAValueSanitizedOnSave(string $markup): void
    {
        $sanitizer = Pimcore::getContainer()->get(Text::PIMCORE_WYSIWYG_SANITIZER_ID);
        $stored = $sanitizer->sanitizeFor('body', $markup);

        $editable = $this->createEditable($stored);

        $this->assertSame($stored, $sanitizer->sanitizeFor('body', $stored));
        $this->assertSame($stored, (string) $editable->frontend());
        $this->assertSame($stored, (string) $editable->getDataEditmode());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function storedMarkupProvider(): array
    {
        return [
            'entities' => ['<p>Fish &amp; Chips&nbsp;&ndash; 5 &lt; 10 &gt; 3 &quot;q&quot; &euro; &copy; ä ö ü ß</p>'],
            'links' => ['<p><a href="https://example.com/?a=1&amp;b=2" target="_blank" rel="noopener">ext</a> '
                . '<a href="mailto:a@example.com">mail</a> <a href="/relative/path" title="t">rel</a></p>'],
            'image' => ['<p><img src="/var/assets/a.jpg" alt="a &quot;b&quot;" style="width:100px" /></p>'],
            'table' => ['<table border="1" cellpadding="2" style="width:100%"><thead><tr><th scope="col">h</th></tr>'
                . '</thead><tbody><tr><td colspan="2" style="text-align:center">a<br />b</td></tr></tbody></table>'],
            'lists and blocks' => ['<ul class="x"><li>one</li><li>two<ol><li>nested</li></ol></li></ul>'
                . '<blockquote>q</blockquote><h2 id="anchor">H</h2><hr />'],
            'inline formatting' => ['<p style="text-align:right" dir="rtl"><span class="a" style="color:#ff0000">c'
                . '</span> <u>u</u> <s>s</s> <sub>1</sub> <sup>2</sup> <em>e</em> <strong>b</strong> <i>i</i></p>'],
            'whitespace' => ["<p>line one</p>\n\n<p>line   two\twith tabs</p>\n"],
        ];
    }
}
