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

use Pimcore\Model\Document\Editable\Wysiwyg;
use Pimcore\Model\Document\Page;
use Pimcore\Tests\Support\Test\TestCase;

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
}
