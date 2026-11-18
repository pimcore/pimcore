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

use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * ClassDefinition::__set_state() calls setCompositeIndices() for every definition it loads, even
 * when the class has none. In admin mode that must not enrich the field definitions, which runs
 * the options provider of every select field of the class.
 */
class CompositeIndicesEnrichmentTest extends TestCase
{
    private ClassDefinition $class;

    protected function setUp(): void
    {
        parent::setUp();

        CountingOptionsProvider::$calls = 0;

        $select = new Data\Select();
        $select->setName('choice');
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass(CountingOptionsProvider::class);

        $relation = new Data\ManyToOneRelation();
        $relation->setName('parentItem');

        $this->class = new ClassDefinition();
        $this->class->setFieldDefinitions(['choice' => $select, 'parentItem' => $relation]);

        Pimcore::setAdminMode();
    }

    protected function tearDown(): void
    {
        Pimcore::unsetAdminMode();

        parent::tearDown();
    }

    public function testProviderRunsWhenFieldDefinitionsAreEnriched(): void
    {
        // guards the tests below against passing because the provider is never wired up
        $this->class->getFieldDefinitions();

        $this->assertGreaterThan(0, CountingOptionsProvider::$calls);
    }

    public function testSettingNoCompositeIndicesDoesNotRunOptionsProviders(): void
    {
        $this->class->setCompositeIndices([]);

        $this->assertSame(0, CountingOptionsProvider::$calls);
    }

    public function testSettingCompositeIndicesDoesNotRunOptionsProvidersAndStillExpandsRelations(): void
    {
        $this->class->setCompositeIndices([
            ['index_key' => 'choice_parent', 'index_columns' => ['choice', 'parentItem']],
        ]);

        $this->assertSame(0, CountingOptionsProvider::$calls);
        $this->assertSame(
            ['choice', 'parentItem__id', 'parentItem__type'],
            $this->class->getCompositeIndices()[0]['index_columns']
        );
    }
}

class CountingOptionsProvider implements SelectOptionsProviderInterface
{
    public static int $calls = 0;

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        self::$calls++;

        return [['key' => 'A', 'value' => 'a']];
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): ?string
    {
        return null;
    }
}
