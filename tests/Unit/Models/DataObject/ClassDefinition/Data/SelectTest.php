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

use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Tests\Support\Test\TestCase;

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
}
