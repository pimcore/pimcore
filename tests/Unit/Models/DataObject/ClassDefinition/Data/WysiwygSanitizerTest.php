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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Wysiwyg;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Text;
use ReflectionProperty;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Regression test for the extension point introduced in pimcore/pimcore#18682:
 * the WYSIWYG data type must accept ANY {@see HtmlSanitizerInterface} implementation,
 * not only Symfony's concrete HtmlSanitizer. If the type hint is ever narrowed back to
 * the concrete class, injecting the custom implementation below throws a TypeError and
 * this test fails.
 *
 * @group unit.model.datatype.wysiwyg
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

    public function testGetDataForResourceDelegatesToCustomHtmlSanitizerImplementation(): void
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

        $field = new Wysiwyg();
        $field->setName('content');

        $result = $field->getDataForResource('<p>hello <b>world</b></p>');

        // the custom implementation was invoked for the persistence sanitization
        $this->assertSame([['body', '<p>hello <b>world</b></p>']], $sanitizer->sanitizeForCalls);
        // and its output is what gets persisted
        $this->assertSame('[[custom-sanitized]]<p>hello <b>world</b></p>', $result);
    }

    public function testGetDataForResourceSkipsSanitizationWhenDisabled(): void
    {
        $sanitizer = new class implements HtmlSanitizerInterface {
            public bool $called = false;

            public function sanitize(string $input): string
            {
                $this->called = true;

                return $input;
            }

            public function sanitizeFor(string $element, string $input): string
            {
                $this->called = true;

                return $input;
            }
        };

        (new ReflectionProperty(Wysiwyg::class, 'pimcoreWysiwygSanitizer'))->setValue(null, $sanitizer);

        $field = new Wysiwyg();
        $field->setName('content');

        $field->getDataForResource('<p>hello</p>', null, ['sanitize' => false]);

        $this->assertFalse($sanitizer->called, 'sanitizer must not run when sanitize=false');
    }
}
