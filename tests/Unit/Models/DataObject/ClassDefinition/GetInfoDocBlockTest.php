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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition;
use ReflectionMethod;

/**
 * getInfoDocBlock() writes the class title into the docblock header of the generated
 * definition_<name>.php file, which is later executed with an @include. A title carrying a
 * docblock terminator closes that comment early, turning the remainder of the title into live PHP
 * at file scope; a trailing comment opener then reopens a comment so the file still parses
 * (GHSA-892j-xq4w-h8xg). The description field right below it was already sanitised; the title
 * was not.
 */
final class GetInfoDocBlockTest extends TestCase
{
    private const TERMINATOR = '*' . '/';

    public function testTitleContainingCommentTerminatorIsNeutralised(): void
    {
        $class = new ClassDefinition();
        $class->setTitle('HuntedClass ' . self::TERMINATOR . " file_put_contents('/tmp/pwn.txt','1'); /*");

        $docBlock = $this->invokeGetInfoDocBlock($class);

        $this->assertStringNotContainsString(self::TERMINATOR, $this->extractTitleLine($docBlock));
        $this->assertStringContainsString(
            'file_put_contents',
            $docBlock,
            'The harmless remainder of the title is still expected to be present.'
        );
        $this->assertSame(
            1,
            substr_count($docBlock, self::TERMINATOR),
            "Only the docblock's own closing delimiter must remain."
        );
    }

    /**
     * Stripping one terminator can splice the surrounding characters into a new one, so a single
     * str_replace() pass — the sanitisation the description line has used since
     * GHSA-9x44-4gxf-8c25 — is not enough on its own.
     */
    public function testTitleCannotSpliceANewTerminatorOutOfTheSanitisedRemainder(): void
    {
        $class = new ClassDefinition();
        $class->setTitle('HuntedClass **' . "// file_put_contents('/tmp/pwn.txt','1'); /*");

        $docBlock = $this->invokeGetInfoDocBlock($class);

        $this->assertStringNotContainsString(self::TERMINATOR, $this->extractTitleLine($docBlock));
        $this->assertSame(1, substr_count($docBlock, self::TERMINATOR));
    }

    public function testDescriptionCannotSpliceANewTerminatorOutOfTheSanitisedRemainder(): void
    {
        $class = new ClassDefinition();
        $class->setDescription('Harmless **' . "// echo 'INJECTED'; /*");

        $docBlock = $this->invokeGetInfoDocBlock($class);

        $this->assertSame(1, substr_count($docBlock, self::TERMINATOR));
    }

    public function testOrdinaryTitleIsPreservedVerbatim(): void
    {
        $class = new ClassDefinition();
        $class->setTitle('Normal title');

        $docBlock = $this->invokeGetInfoDocBlock($class);

        $this->assertStringContainsString(' * Title: Normal title' . "\n", $docBlock);
    }

    private function invokeGetInfoDocBlock(ClassDefinition $class): string
    {
        $method = new ReflectionMethod(ClassDefinition::class, 'getInfoDocBlock');
        $method->setAccessible(true);

        return $method->invoke($class);
    }

    private function extractTitleLine(string $docBlock): string
    {
        foreach (explode("\n", $docBlock) as $line) {
            if (str_starts_with(trim($line), '* Title:')) {
                return $line;
            }
        }

        return '';
    }
}
