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
            'in scope next to out of scope' => [['title' => 'fine', 'noteditable' => true]],
        ];
    }

    /**
     * @dataProvider outOfScopeOverrideProvider
     */
    public function testAnOutOfScopeOverrideIsRefused(array $override): void
    {
        $relation = $this->relation(new Input());
        $relation->setDefinitionOverride(['title' => 'stored']);

        try {
            $relation->setDefinitionOverride($override);
            $this->fail('An out of scope override must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(['title' => 'stored'], $relation->getDefinitionOverride(), 'nothing was taken over');
    }

    public function testEveryInScopePropertyIsAccepted(): void
    {
        $relation = $this->relation(new QuantityValue());
        $override = [
            'title' => 'Height',
            'tooltip' => 'in this group only',
            'options' => null,
            'defaultValue' => 1,
            'defaultUnit' => 'cm',
            'validUnits' => ['cm', 'mm'],
        ];

        $relation->setDefinitionOverride($override);

        $this->assertSame($override, $relation->getDefinitionOverride());
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
