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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Helper;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition\Helper\DocBlockSanitizer;

/**
 * Values that end up inside a generated PHPDoc block (field name, field title, class title,
 * class description, PHPDoc type) are attacker-controlled. If any comment delimiter survives,
 * the docblock closes early and the rest of the value becomes live PHP in a generated file that
 * is autoloaded or @include-d.
 */
final class DocBlockSanitizerTest extends TestCase
{
    /**
     * @dataProvider maliciousValueProvider
     */
    public function testNoCommentDelimiterSurvives(string $value): void
    {
        $sanitized = DocBlockSanitizer::sanitize($value);

        foreach (['/**', '*' . '/', '//'] as $delimiter) {
            $this->assertStringNotContainsString(
                $delimiter,
                $sanitized,
                sprintf('"%s" must not survive sanitisation of %s', $delimiter, var_export($value, true))
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function maliciousValueProvider(): iterable
    {
        yield 'plain terminator' => ['*' . '/'];
        yield 'docblock opener' => ['/**'];
        yield 'line comment' => ['//'];

        // A single str_replace() pass leaves a live terminator behind here: removing the inner
        // "*/" splices the outer "*" and "/" together. This is the case the one-pass sanitiser
        // that guarded the field name and title since GHSA-9x44-4gxf-8c25 never caught.
        yield 'terminator spliced by a single pass' => ['**' . '//'];
        yield 'nested opener spliced by a single pass' => ['//**' . '**//'];
        yield 'repeated splice' => [str_repeat('*', 8) . str_repeat('/', 8)];
        yield 'relation type payload' => [
            '\Pimcore\Model\DataObject\Foo**' . "// } echo 'INJECTED'; __halt_compiler();",
        ];
    }

    /**
     * @dataProvider legitimateValueProvider
     */
    public function testLegitimateValuesArePassedThroughUnchanged(string $value): void
    {
        $this->assertSame($value, DocBlockSanitizer::sanitize($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function legitimateValueProvider(): iterable
    {
        yield 'fully qualified relation type' => ['\Pimcore\Model\DataObject\Product|null'];
        yield 'union of relation types' => ['\Pimcore\Model\DataObject\Foo|\Pimcore\Model\DataObject\Bar|null'];
        yield 'generic fieldcollection type' => ['\Pimcore\Model\DataObject\Fieldcollection<\Pimcore\Model\DataObject\Fieldcollection\Data\Item>|null'];
        yield 'scalar type' => ['?string'];
        yield 'array shape' => ['array'];
        yield 'human readable title' => ['Product name (EN) - primary'];
        yield 'title with a single asterisk' => ['Price * quantity'];
        yield 'title with a single slash' => ['Width / height'];
    }

    public function testNullIsNormalisedToAnEmptyString(): void
    {
        $this->assertSame('', DocBlockSanitizer::sanitize(null));
    }
}
