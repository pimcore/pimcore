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

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Tests\Support\Test\TestCase;

class ServiceTest extends TestCase
{
    private const BRICK_KEY = 'csEngineBrick';

    protected function tearDown(): void
    {
        RuntimeCache::getInstance()->offsetUnset('objectbrick_' . self::BRICK_KEY);

        parent::tearDown();
    }

    /**
     * The pre-validation pass and classSaved() have to agree on which fields are classification
     * store fields: classSaved() is dispatched into localized fields as well, so the collector the
     * validation uses must reach the same ones.
     */
    public function testTheCollectorFindsClassificationStoreFieldsInsideLocalizedFields(): void
    {
        $localized = new Localizedfields();
        $localized->setName('localizedfields');
        $localized->setChildren([$this->classificationStoreField('csLocalized'), new Input()]);

        $fieldDefinitions = [
            'plain' => new Input(),
            'csTopLevel' => $this->classificationStoreField('csTopLevel'),
            'localizedfields' => $localized,
        ];

        $this->assertSame(
            ['csTopLevel', 'csLocalized'],
            array_keys(Service::getClassificationStoreFieldDefinitions($fieldDefinitions))
        );
    }

    /**
     * Objectbricks dispatch classSaved() with the owning class as well, so a classification store
     * inside a brick has to be reachable from the class's own field definitions.
     */
    public function testTheCollectorFindsClassificationStoreFieldsInsideObjectBricks(): void
    {
        $this->registerBrick($this->classificationStoreField('csInBrick', 5));

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::BRICK_KEY]);

        $collected = Service::getClassificationStoreFieldDefinitions(['bricks' => $bricks]);

        $this->assertSame(['csInBrick'], array_keys($collected));
        $this->assertSame(5, Service::getConfiguredStoreId($collected['csInBrick']));
    }

    public function testAClassWithoutClassificationStoreFieldsCollectsNothing(): void
    {
        $this->assertSame([], Service::getClassificationStoreFieldDefinitions(['plain' => new Input()]));
    }

    /**
     * storeId is a typed property without a default, so an uninitialised one must be reported as
     * "no store" rather than raise an Error or be mistaken for store 1.
     */
    public function testTheConfiguredStoreIdIsNullWhenTheFieldCarriesNone(): void
    {
        $this->assertNull(Service::getConfiguredStoreId($this->classificationStoreField('cs')));
    }

    public function testTheConfiguredStoreIdIsTheStoreOfTheField(): void
    {
        $this->assertSame(4, Service::getConfiguredStoreId($this->classificationStoreField('cs', 4)));
    }

    private function classificationStoreField(string $name, ?int $storeId = null): ClassificationstoreField
    {
        $fieldDefinition = new ClassificationstoreField();
        $fieldDefinition->setName($name);

        if ($storeId !== null) {
            $fieldDefinition->setStoreId($storeId);
        }

        return $fieldDefinition;
    }

    /**
     * Definition::getByKey() answers from the runtime cache before it looks for a definition file,
     * so the brick can be built in memory.
     */
    private function registerBrick(ClassificationstoreField $fieldDefinition): void
    {
        $brick = new Definition();
        $brick->setKey(self::BRICK_KEY);
        $brick->setFieldDefinitions([$fieldDefinition->getName() => $fieldDefinition]);

        RuntimeCache::set('objectbrick_' . self::BRICK_KEY, $brick);
    }
}
