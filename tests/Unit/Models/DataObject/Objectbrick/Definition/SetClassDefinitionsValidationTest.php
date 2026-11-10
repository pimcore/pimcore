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
 * Ensures Objectbrick\Definition::setClassDefinitions() only accepts valid classname/fieldname
 * identifiers. Both are emitted verbatim into generated PHP container classes and into
 * container-class file paths (PHPObjectBrickContainerClassDumper::dumpContainerClasses(),
 * Definition::cleanupOldFiles()), so an unvalidated entry previously allowed code injection via
 * generated PHP and arbitrary .php file deletion via path traversal (GHSA-x52m-55rx-hq3w).
 */
class SetClassDefinitionsValidationTest extends TestCase
{
    public function testValidClassDefinitionsAreAccepted(): void
    {
        $definition = new Definition();
        $valid = [
            ['classname' => 'Product', 'fieldname' => 'myBricks'],
            ['classname' => 'Customer_1', 'fieldname' => '_internal_brick'],
        ];

        $definition->setClassDefinitions($valid);
        $this->assertSame($valid, $definition->getClassDefinitions());
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

    public static function invalidClassDefinitionsProvider(): array
    {
        return [
            'fieldname with path traversal' => [
                ['classname' => 'Product', 'fieldname' => '../../DataObject/Product'],
            ],
            'fieldname with PHP code characters' => [
                ['classname' => 'Product', 'fieldname' => 'Bricks {} //'],
            ],
            'fieldname with trailing newline' => [
                ['classname' => 'Product', 'fieldname' => "myBricks\n"],
            ],
            'classname with path traversal' => [
                ['classname' => '../Product', 'fieldname' => 'myBricks'],
            ],
            'classname with trailing newline' => [
                ['classname' => "Product\n", 'fieldname' => 'myBricks'],
            ],
        ];
    }
}
