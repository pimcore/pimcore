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

namespace Pimcore\Tests\Unit\Model\DataObject\Classificationstore;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The definition a key assignment effectively uses, and what an assignment is allowed to override.
 */
class KeyGroupRelationTest extends TestCase
{
    private const KEY_NAME = 'csOverride';

    private const RED = ['key' => 'Red', 'value' => 'red'];

    private const GREEN = ['key' => 'Green', 'value' => 'green'];

    private const BLUE = ['key' => 'Blue', 'value' => 'blue'];

    /**
     * Everything an override may carry, and nothing else.
     */
    private const IN_SCOPE = ['options' => null, 'defaultUnit' => 'cm', 'validUnits' => ['cm', 'mm']];

    public function testWithoutAnOverrideTheEffectiveDefinitionIsTheKeysOwn(): void
    {
        $relation = $this->relation(new Input());

        $this->assertSame($relation->getDefinition(), $relation->getEffectiveDefinition());
    }

    public function testAUnitOverrideChangesOnlyTheUnit(): void
    {
        $definition = new QuantityValue();
        $definition->setDefaultUnit('mm');
        $relation = $this->relation($definition);

        $relation->setDefinitionOverride(['defaultUnit' => 'cm']);

        $own = json_decode($relation->getDefinition(), true);
        $effective = json_decode($relation->getEffectiveDefinition(), true);

        $this->assertSame('cm', $effective['defaultUnit']);
        $this->assertSame('mm', $own['defaultUnit'], 'the key keeps its own unit');

        unset($own['defaultUnit'], $effective['defaultUnit']);
        $this->assertSame($own, $effective, 'nothing else differs');
    }

    public function testAnOptionSubsetFiltersTheKeysOwnOptions(): void
    {
        $definition = new Select();
        $definition->setOptions([self::RED, self::GREEN, self::BLUE]);
        $relation = $this->relation($definition);

        // deliberately not in the key's order, and the key's order is what the result keeps
        $relation->setDefinitionOverride(['options' => ['blue', 'red']]);

        $effective = json_decode($relation->getEffectiveDefinition(), true);

        $this->assertSame([self::RED, self::BLUE], $effective['options']);
        $this->assertSame(
            [self::RED, self::GREEN, self::BLUE],
            json_decode($relation->getDefinition(), true)['options'],
            'the key keeps its full option list'
        );
    }

    public function testANullSubsetMeansEveryOptionAndAnEmptyOneMeansNone(): void
    {
        $definition = new Select();
        $definition->setOptions([self::RED, self::GREEN]);
        $relation = $this->relation($definition);

        $relation->setDefinitionOverride(['options' => null]);
        $this->assertSame(
            [self::RED, self::GREEN],
            json_decode($relation->getEffectiveDefinition(), true)['options']
        );

        $relation->setDefinitionOverride(['options' => []]);
        $this->assertSame([], json_decode($relation->getEffectiveDefinition(), true)['options']);
    }

    public static function outOfScopeOverrideProvider(): array
    {
        return [
            'fieldtype' => [['fieldtype' => 'input']],
            'type' => [['type' => 'input']],
            'name' => [['name' => 'somethingElse']],
            'mandatory' => [['mandatory' => true]],
            // a per group label is localization's question, not a definition override's
            'title' => [['title' => 'Height']],
            'tooltip' => [['tooltip' => 'in this group only']],
            'defaultValue' => [['defaultValue' => 1]],
            'in scope next to out of scope' => [['defaultUnit' => 'cm', 'noteditable' => true]],
        ];
    }

    /**
     * @dataProvider outOfScopeOverrideProvider
     */
    public function testAnOutOfScopeOverrideIsRefused(array $override): void
    {
        $relation = $this->relation(new Input());
        $relation->setDefinitionOverride(self::IN_SCOPE);

        try {
            $relation->setDefinitionOverride($override);
            $this->fail('An out of scope override must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(self::IN_SCOPE, $relation->getDefinitionOverride(), 'nothing was taken over');
    }

    public function testTheClosedListOfOverridablePropertiesIsAccepted(): void
    {
        $relation = $this->relation(new QuantityValue());

        $relation->setDefinitionOverride(self::IN_SCOPE);

        $this->assertSame(self::IN_SCOPE, $relation->getDefinitionOverride());
    }

    public static function illTypedOverrideProvider(): array
    {
        return [
            // the value `filterOptions()` would then be handed instead of a subset
            'options as a string' => [['options' => 'red']],
            'options as an int' => [['options' => 1]],
            // the arguments of AbstractQuantityValue::setDefaultUnit() and ::setValidUnits()
            'defaultUnit as a list' => [['defaultUnit' => ['cm']]],
            'defaultUnit as an int' => [['defaultUnit' => 7]],
            'validUnits as a string' => [['validUnits' => 'cm']],
            'validUnits as null' => [['validUnits' => null]],
            'ill typed next to well typed' => [['defaultUnit' => 'cm', 'validUnits' => 'mm']],
        ];
    }

    /**
     * @dataProvider illTypedOverrideProvider
     */
    public function testAnOverrideOfTheWrongValueTypeIsRefused(array $override): void
    {
        $relation = $this->relation(new QuantityValue());
        $relation->setDefinitionOverride(self::IN_SCOPE);

        try {
            $relation->setDefinitionOverride($override);
            $this->fail('An override of the wrong value type must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(self::IN_SCOPE, $relation->getDefinitionOverride(), 'nothing was taken over');
    }

    /**
     * What the refusal buys: an accepted override is one `getEffectiveDefinition()` can merge and
     * the quantity value setters can take back, rather than one that only fails when read.
     */
    public function testAnAcceptedOverrideSurvivesTheMergeAndTheQuantityValueSetters(): void
    {
        $definition = new QuantityValue();
        $definition->setDefaultUnit('mm');
        $definition->setValidUnits(['mm']);
        $relation = $this->relation($definition);

        foreach ([null, [], ['cm']] as $options) {
            $relation->setDefinitionOverride(
                ['options' => $options, 'defaultUnit' => 'cm', 'validUnits' => ['cm', 'mm']]
            );

            $effective = json_decode($relation->getEffectiveDefinition(), true);

            $merged = new QuantityValue();
            $merged->setDefaultUnit($effective['defaultUnit']);
            $merged->setValidUnits($effective['validUnits']);

            $this->assertSame('cm', $merged->getDefaultUnit());
            $this->assertSame(['cm', 'mm'], $merged->getValidUnits());
        }
    }

    public function testMetadataThatCannotBeEncodedAsJsonIsRefused(): void
    {
        $relation = $this->relation(new Input());
        $relation->setMetadata(['externalCode' => 'EF000001']);

        try {
            // a byte sequence that is not valid UTF-8, which json_encode() cannot represent
            $relation->setMetadata(['externalCode' => "EF\xB1\x31"]);
            $this->fail('Metadata that json_encode() cannot represent must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(['externalCode' => 'EF000001'], $relation->getMetadata(), 'nothing was taken over');
    }

    /**
     * The column is `VARCHAR(190)`, and Pimcore ships with `sql_mode` empty, so a longer value
     * would be truncated on save without a word - and a truncated sub-group is a different one.
     * 190 is a count of characters, not of bytes, which is why the boundary is checked in a
     * multi-byte alphabet too.
     */
    public function testASubGroupLongerThanItsColumnIsRefused(): void
    {
        $relation = $this->relation(new Input());

        $relation->setSubGroup(str_repeat('ä', 190));
        $this->assertSame(str_repeat('ä', 190), $relation->getSubGroup(), '190 characters still fit');

        foreach ([str_repeat('a', 191), str_repeat('ä', 191)] as $tooLong) {
            try {
                $relation->setSubGroup($tooLong);
                $this->fail('A sub-group longer than 190 characters must be refused');
            } catch (InvalidConfigException $e) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(str_repeat('ä', 190), $relation->getSubGroup(), 'nothing was taken over');
    }

    private function relation(Data $definition): KeyGroupRelation
    {
        $definition->setName(self::KEY_NAME);

        $relation = new KeyGroupRelation();
        $relation->setGroupId(1);
        $relation->setKeyId(2);
        $relation->setType($definition->getFieldType());
        $relation->setDefinition(json_encode($definition));

        return $relation;
    }
}
