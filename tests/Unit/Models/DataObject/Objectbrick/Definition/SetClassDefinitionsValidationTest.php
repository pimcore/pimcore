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

namespace Pimcore\Tests\Unit\Model\DataObject\Objectbrick\Definition;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * The classname and fieldname of an Objectbrick class definition entry are used to build generated class files and
 * the paths they are written to, so Objectbrick\Definition only accepts plain identifiers for them. This applies when
 * the entries are set (setClassDefinitions(), hydration from the definition file via setValues()) and again when the
 * definition is saved, because the classDefinitions property is public.
 */
class SetClassDefinitionsValidationTest extends TestCase
{
    public function testValidClassDefinitionsAreAccepted(): void
    {
        $definition = new Definition();
        $valid = [
            ['classname' => 'Product', 'fieldname' => 'myBricks'],
            ['classname' => 'Customer_1', 'fieldname' => '_internal_brick'],
            ['classname' => '12', 'fieldname' => 'a'],
            ['classname' => 'Product', 'fieldname' => str_repeat('a', 63)],
            ['classname' => 'Product', 'fieldname' => 'myBricks', 'deleted' => true],
        ];

        $definition->setClassDefinitions($valid);
        $this->assertSame($valid, $definition->getClassDefinitions());
    }

    public function testNoClassDefinitionsAreAccepted(): void
    {
        $definition = new Definition();
        $definition->setClassDefinitions([]);

        $this->assertSame([], $definition->getClassDefinitions());
    }

    public function testEmptyValuesAreAcceptedWhileSetting(): void
    {
        // empty values are set transiently by the framework and must not throw
        $definition = new Definition();
        $definition->setClassDefinitions([['classname' => '', 'fieldname' => '']]);

        $this->assertSame([['classname' => '', 'fieldname' => '']], $definition->getClassDefinitions());
    }

    /**
     * @dataProvider invalidClassDefinitionsProvider
     */
    public function testInvalidClassDefinitionsAreRejected(array $entry): void
    {
        $this->expectException(InvalidArgumentException::class);

        $definition = new Definition();
        $definition->setClassDefinitions([$entry]);
    }

    /**
     * @dataProvider invalidClassDefinitionsProvider
     */
    public function testInvalidClassDefinitionsAreRejectedWhenHydrating(array $entry): void
    {
        $this->expectException(InvalidArgumentException::class);

        Definition::__set_state(['key' => 'MyBrick', 'classDefinitions' => [$entry]]);
    }

    /**
     * @dataProvider invalidClassDefinitionsProvider
     */
    public function testInvalidClassDefinitionsAreRejectedWhenSaving(array $entry): void
    {
        $definition = new Definition();
        $definition->setKey('MyBrick');
        // the property is public, so it can be assigned without going through the setter
        $definition->classDefinitions = [$entry];

        try {
            $definition->save();
            $this->fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('classDefinitions', $e->getMessage());
        }
    }

    public function testEmptyValuesAreRejectedWhenSaving(): void
    {
        $definition = new Definition();
        $definition->setKey('MyBrick');
        $definition->setClassDefinitions([['classname' => 'Product', 'fieldname' => '']]);

        $this->expectException(InvalidArgumentException::class);

        $definition->save();
    }

    public static function invalidClassDefinitionsProvider(): array
    {
        return [
            'fieldname with path separators' => [
                ['classname' => 'Product', 'fieldname' => '../other/Product'],
            ],
            'fieldname with non-identifier characters' => [
                ['classname' => 'Product', 'fieldname' => 'my field;'],
            ],
            'fieldname with a dash' => [
                ['classname' => 'Product', 'fieldname' => 'my-field'],
            ],
            'fieldname starting with a digit' => [
                ['classname' => 'Product', 'fieldname' => '1field'],
            ],
            'fieldname that is too long' => [
                ['classname' => 'Product', 'fieldname' => str_repeat('a', 64)],
            ],
            // PCRE `$` also matches before a final newline; only `\z` is an absolute end anchor.
            'fieldname with trailing newline' => [
                ['classname' => 'Product', 'fieldname' => "myBricks\n"],
            ],
            'classname with path separators' => [
                ['classname' => '../Product', 'fieldname' => 'myBricks'],
            ],
            'classname with non-identifier characters' => [
                ['classname' => 'Pro duct;', 'fieldname' => 'myBricks'],
            ],
            'classname with trailing newline' => [
                ['classname' => "Product\n", 'fieldname' => 'myBricks'],
            ],
            'classname is not a string' => [
                ['classname' => ['Product'], 'fieldname' => 'myBricks'],
            ],
            'fieldname is not a string' => [
                ['classname' => 'Product', 'fieldname' => 123],
            ],
            'missing fieldname' => [
                ['classname' => 'Product'],
            ],
            'missing classname' => [
                ['fieldname' => 'myBricks'],
            ],
            'entry without keys' => [
                [],
            ],
        ];
    }

    public function testEntryThatIsNotAnArrayIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Definition())->setClassDefinitions(['Product']);
    }
}
