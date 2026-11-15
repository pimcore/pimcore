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
use Pimcore\Model\Document\Editable\Dao;
use Pimcore\Model\Document\Editable\Wysiwyg;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Text;
use ReflectionProperty;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Regression test for the extension point introduced in pimcore/pimcore#18682:
 * saving a WYSIWYG document editable must delegate to whatever {@see HtmlSanitizerInterface}
 * implementation is configured, not only Symfony's concrete HtmlSanitizer. Narrowing the type
 * hint back to the concrete class makes injecting the custom implementation below throw a
 * TypeError, failing this test.
 *
 * @group unit.document.editable.wysiwyg
 */
class WysiwygSanitizerTest extends TestCase
{
    protected function tearDown(): void
    {
        // restore the real container sanitizer so the fake does not leak into other tests
        $sanitizer = Pimcore::getContainer()->get(Text::PIMCORE_WYSIWYG_SANITIZER_ID);
        (new ReflectionProperty(Wysiwyg::class, 'pimcoreWysiwygSanitizer'))->setValue(null, $sanitizer);

        parent::tearDown();
    }

    public function testSaveDelegatesToCustomHtmlSanitizerImplementation(): void
    {
        // a custom sanitizer that implements the interface directly (does NOT extend Symfony's concrete HtmlSanitizer)
        $sanitizer = new class implements HtmlSanitizerInterface {
            /** @var array<int, array{0: string, 1: string}> */
            public array $sanitizeForCalls = [];

            public function sanitize(string $input): string
            {
                return $input;
            }

            public function sanitizeFor(string $element, string $input): string
            {
                $this->sanitizeForCalls[] = [$element, $input];

                return '[[custom-sanitized]]' . $input;
            }
        };

        (new ReflectionProperty(Wysiwyg::class, 'pimcoreWysiwygSanitizer'))->setValue(null, $sanitizer);

        // avoid touching the database - we only care about the sanitization step of save()
        $dao = $this->createMock(Dao::class);
        $dao->expects($this->once())->method('save');

        $editable = new Wysiwyg();
        $editable->setName('content');
        $editable->setDao($dao);
        $editable->setDataFromEditmode('<p>hello <b>world</b></p>');

        $editable->save();

        // the custom implementation was invoked on save ...
        $this->assertSame([['body', '<p>hello <b>world</b></p>']], $sanitizer->sanitizeForCalls);
        // ... and its output is what the editable stores
        $this->assertSame('[[custom-sanitized]]<p>hello <b>world</b></p>', $editable->getText());
    }
}
