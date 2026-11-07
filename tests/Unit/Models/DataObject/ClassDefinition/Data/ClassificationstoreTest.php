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

use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;

/**
 * The field-level localization flag after the per key flag landed: deprecated in favour of it, and
 * unchanged in signature, default and meaning as the default for the keys that carry none.
 */
class ClassificationstoreTest extends TestCase
{
    public function testTheFieldLevelLocalizationFlagIsDeprecatedInFavourOfTheKeyFlag(): void
    {
        $reflection = new ReflectionClass(Classificationstore::class);

        $docComments = [
            '$localized' => (string) $reflection->getProperty('localized')->getDocComment(),
            'isLocalized()' => (string) $reflection->getMethod('isLocalized')->getDocComment(),
            'setLocalized()' => (string) $reflection->getMethod('setLocalized')->getDocComment(),
        ];

        foreach ($docComments as $member => $docComment) {
            $this->assertStringContainsString('@deprecated', $docComment, $member . ' must be deprecated');
            $this->assertStringContainsString(
                'KeyConfig',
                $docComment,
                $member . ' must name the per key flag that replaces it'
            );
        }
    }

    public function testTheDeprecatedFlagKeepsItsSignatureAndItsDefault(): void
    {
        $field = new Classificationstore();

        $this->assertFalse($field->isLocalized(), 'the inherited default stays off');

        $field->setLocalized(true);
        $this->assertTrue($field->isLocalized());

        $setter = (new ReflectionClass(Classificationstore::class))->getMethod('setLocalized');
        $this->assertSame('bool', (string) $setter->getParameters()[0]->getType());
        $this->assertSame('void', (string) $setter->getReturnType());
    }
}
