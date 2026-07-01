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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;

/**
 * Ensures Data::setName() only accepts valid identifier field names. A field name is emitted
 * verbatim into the generated PHP class files (property, getter/setter, constant) and into
 * ALTER TABLE DDL, so it must be a valid identifier. setName() is the central write path
 * (import/editor/programmatic); class loading uses __set_state() and is intentionally not affected.
 */
class SetNameValidationTest extends TestCase
{
    /**
     * @dataProvider validNameProvider
     */
    public function testValidNamesAreAccepted(string $name): void
    {
        $field = new Input();
        $field->setName($name);

        $this->assertSame($name, $field->getName());
    }

    public static function validNameProvider(): array
    {
        return [
            'simple' => ['myField'],
            'leading underscore' => ['_internal'],
            'single char' => ['a'],
            'digits and underscores' => ['field_123'],
            'max length (63)' => [str_repeat('a', 63)],
        ];
    }

    public function testEmptyNameIsAccepted(): void
    {
        // An empty name is set transiently by the framework and must not throw.
        $field = new Input();
        $field->setName('');

        $this->assertSame('', $field->getName());
    }

    /**
     * @dataProvider invalidNameProvider
     */
    public function testInvalidNamesAreRejected(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Input())->setName($name);
    }

    public static function invalidNameProvider(): array
    {
        return [
            'contains semicolon and braces' => ['x;function __construct(){}//'],
            'contains backtick' => ['poc` varchar(1), col `x'],
            'leading digit' => ['1field'],
            'contains space' => ['my field'],
            'contains dash' => ['my-field'],
            'too long (64)' => [str_repeat('a', 64)],
        ];
    }
}
