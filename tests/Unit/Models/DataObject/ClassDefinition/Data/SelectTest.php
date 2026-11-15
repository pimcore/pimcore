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

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * @group unit.model.datatype.select
 */
class SelectTest extends TestCase
{
    /**
     * Regression test for platform-version#260: a select field whose "options" were never
     * configured serialized "options" as null. The Studio class editor feeds this value
     * straight into a grid that spreads/iterates it (`[...options, newRow]`), which throws
     * "TypeError: e is not iterable" as soon as the first option row is added.
     */
    public function testJsonSerializeDefaultsOptionsToEmptyArrayWhenUnconfigured(): void
    {
        $select = new Select();
        $select->setName('status');

        $this->assertNull($select->getOptions(), 'internal state must stay null (used as an "unresolved" sentinel elsewhere)');

        $serialized = $select->jsonSerialize();

        $this->assertSame([], $serialized['options']);
    }

    public function testJsonSerializeKeepsConfiguredOptionsAsIs(): void
    {
        $select = new Select();
        $select->setName('status');
        $select->setOptions([
            ['key' => 'Open', 'value' => 'open'],
            ['key' => 'Closed', 'value' => 'closed'],
        ]);

        $serialized = $select->jsonSerialize();

        $this->assertSame([
            ['key' => 'Open', 'value' => 'open'],
            ['key' => 'Closed', 'value' => 'closed'],
        ], $serialized['options']);
    }

    public function testEmptyStringDefaultValueIsStoredAsNull(): void
    {
        $select = new Select();
        $select->setName('status');
        $select->setDefaultValue('');

        $this->assertNull($select->getDefaultValue());
    }

    public function testConfiguredDefaultValueIsKept(): void
    {
        $select = new Select();
        $select->setName('status');
        $select->setDefaultValue('open');

        $this->assertSame('open', $select->getDefaultValue());

        $select->setDefaultValue(null);

        $this->assertNull($select->getDefaultValue());
    }

    /**
     * Definition files written with an empty default are rehydrated through __set_state(),
     * so they must not bring the empty string back.
     */
    public function testEmptyStringDefaultValueInStoredDefinitionIsLoadedAsNull(): void
    {
        /** @var Select $select */
        $select = Select::__set_state([
            'name' => 'status',
            'defaultValue' => '',
            'optionsProviderType' => 'class',
        ]);

        $this->assertNull($select->getDefaultValue());
    }

    public function testEmptyStringDefaultValueFromLayoutConfigIsLoadedAsNull(): void
    {
        $layout = Service::generateLayoutTreeFromArray([
            'fieldtype' => 'panel',
            'datatype' => 'layout',
            'name' => 'root',
            'children' => [
                [
                    'fieldtype' => 'select',
                    'datatype' => 'data',
                    'name' => 'status',
                    'defaultValue' => '',
                ],
            ],
        ], true);

        /** @var Select $select */
        $select = $layout->getChildren()[0];

        $this->assertNull($select->getDefaultValue());
    }

    public function testEmptyStringDefaultFromOptionsProviderIsTreatedAsNull(): void
    {
        $this->assertNull($this->resolveProviderDefault(''));
    }

    public function testDefaultFromOptionsProviderIsKept(): void
    {
        $this->assertSame('open', $this->resolveProviderDefault('open'));
        $this->assertNull($this->resolveProviderDefault(null));
    }

    private function resolveProviderDefault(?string $providerDefault): ?string
    {
        // the provider is instantiated by its class name without arguments, so the default is static
        $provider = new class() implements SelectOptionsProviderInterface {
            public static ?string $default = null;

            public function getOptions(array $context, Data $fieldDefinition): array
            {
                return [];
            }

            public function hasStaticOptions(array $context, Data $fieldDefinition): bool
            {
                return false;
            }

            public function getDefaultValue(array $context, Data $fieldDefinition): ?string
            {
                return self::$default;
            }
        };
        $provider::$default = $providerDefault;

        $select = new Select();
        $select->setName('status');
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass($provider::class);

        $method = new ReflectionMethod($select, 'doGetDefaultValue');

        // ClassDefinition is final and cannot be doubled, so the mock must be given a real instance
        $object = $this->createMock(Concrete::class);
        $object->method('getClass')->willReturn(new ClassDefinition());

        return $method->invoke($select, $object);
    }
}
