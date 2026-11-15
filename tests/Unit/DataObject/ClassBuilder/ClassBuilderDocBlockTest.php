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

namespace Pimcore\Tests\Unit\DataObject\ClassBuilder;

use PHPUnit\Framework\TestCase;
use Pimcore\DataObject\ClassBuilder\ClassBuilder;
use Pimcore\DataObject\ClassBuilder\FieldDefinitionBuilderInterface;
use Pimcore\DataObject\ClassBuilder\FieldDefinitionDocBlockBuilderInterface;
use Pimcore\DataObject\ClassBuilder\FieldDefinitionPropertiesBuilderInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * buildClass() writes the class description into the header docblock of the generated model class,
 * which PHPClassDumper::dumpPHPClasses() then writes to the class file that is autoloaded on every
 * object load. A description carrying a docblock terminator closes that comment early and turns the
 * remainder into live PHP at file scope.
 *
 * This site kept a single-pass str_replace() after the sanitiser was centralised, because its search
 * array has a fourth element for the newline rewrite and so did not match a search for the
 * three-element form. A single pass is not enough: dropping one sequence splices the surrounding
 * characters into a new one.
 */
final class ClassBuilderDocBlockTest extends TestCase
{
    private const TERMINATOR = '*' . '/';

    /**
     * @dataProvider maliciousDescriptionProvider
     */
    public function testMaliciousDescriptionCannotCloseTheGeneratedClassDocBlock(string $description): void
    {
        $generated = $this->buildClassSource($description);

        // buildClass() emits several docblocks of its own, so count against a benign baseline
        // rather than a fixed number: the payload must not add a single delimiter.
        $baseline = $this->buildClassSource('An ordinary description');

        foreach (['/**', self::TERMINATOR, '//'] as $delimiter) {
            $this->assertSame(
                substr_count($baseline, $delimiter),
                substr_count($generated, $delimiter),
                sprintf('the description must not add a "%s" to the generated class', $delimiter)
            );
        }

        $this->assertStringNotContainsString(
            self::TERMINATOR,
            $this->extractDescriptionLine($generated),
            'the emitted description line must not contain a docblock terminator'
        );

        $this->assertStringContainsString(
            'INJECTED',
            $generated,
            'the payload text itself stays, as inert comment content - only the delimiters are removed'
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function maliciousDescriptionProvider(): iterable
    {
        yield 'plain terminator' => ['Harmless ' . self::TERMINATOR . " echo 'INJECTED'; /*"];

        // The case the single-pass replacement turned into a live terminator instead of removing it.
        yield 'terminator spliced by a single pass' => ['Harmless **' . "// echo 'INJECTED'; /*"];

        yield 'repeated splice' => [str_repeat('*', 6) . str_repeat('/', 6) . " echo 'INJECTED';"];
    }

    public function testOrdinaryDescriptionIsPreservedAndStillPrefixedPerLine(): void
    {
        $generated = $this->buildClassSource("First line\nSecond line");

        $this->assertStringContainsString(" * First line\n * Second line\n", $generated);
    }

    public function testDescriptionWithASingleAsteriskOrSlashIsUntouched(): void
    {
        $generated = $this->buildClassSource('Price * quantity, width / height');

        $this->assertStringContainsString(' * Price * quantity, width / height' . "\n", $generated);
    }

    private function extractDescriptionLine(string $generated): string
    {
        foreach (explode("\n", $generated) as $line) {
            if (str_contains($line, 'INJECTED')) {
                return $line;
            }
        }

        return '';
    }

    private function buildClassSource(string $description): string
    {
        $classDefinition = new ClassDefinition();
        $classDefinition->setName('unittest');
        $classDefinition->setDescription($description);

        $builder = new ClassBuilder(
            $this->createMock(FieldDefinitionDocBlockBuilderInterface::class),
            $this->createMock(FieldDefinitionPropertiesBuilderInterface::class),
            $this->createMock(FieldDefinitionBuilderInterface::class),
        );

        return $builder->buildClass($classDefinition);
    }
}
