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

    private const OTHER_BRICK_KEY = 'csEngineOtherBrick';

    protected function tearDown(): void
    {
        foreach ([self::BRICK_KEY, self::OTHER_BRICK_KEY] as $brickKey) {
            RuntimeCache::getInstance()->offsetUnset('objectbrick_' . $brickKey);
        }

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

        $this->assertSame([self::BRICK_KEY . '.csInBrick'], array_keys($collected));
        $this->assertSame(5, Service::getConfiguredStoreId($collected[self::BRICK_KEY . '.csInBrick']));
    }

    /**
     * Two bricks allowed on the same field may each define a classification store under the same
     * name, on different stores. Keying the collection by name alone dropped the second one, and
     * with it the engine validation of the store behind it.
     */
    public function testTwoBricksDefiningTheSameFieldNameAreBothCollected(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5));
        $this->registerBrick($this->classificationStoreField('cs', 6), self::OTHER_BRICK_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::BRICK_KEY, self::OTHER_BRICK_KEY]);

        $collected = Service::getClassificationStoreFieldDefinitions(['bricks' => $bricks]);

        $this->assertSame(
            [self::BRICK_KEY . '.cs', self::OTHER_BRICK_KEY . '.cs'],
            array_keys($collected)
        );
        $this->assertSame(5, Service::getConfiguredStoreId($collected[self::BRICK_KEY . '.cs']));
        $this->assertSame(6, Service::getConfiguredStoreId($collected[self::OTHER_BRICK_KEY . '.cs']));
    }

    /**
     * The value tables key their rows by (object, fieldname), so the definitions sharing a name are
     * what has to be resolved together.
     */
    public function testTheStoresOfTheDefinitionsSharingAFieldNameAreGroupedUnderIt(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5));
        $this->registerBrick($this->classificationStoreField('cs', 6), self::OTHER_BRICK_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::BRICK_KEY, self::OTHER_BRICK_KEY]);

        $this->assertSame(
            [
                'other' => ['other' => null],
                'cs' => [self::BRICK_KEY . '.cs' => 5, self::OTHER_BRICK_KEY . '.cs' => 6],
            ],
            Service::groupStoreIdsByFieldName(Service::getClassificationStoreFieldDefinitions([
                'other' => $this->classificationStoreField('other'),
                'bricks' => $bricks,
            ]))
        );
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
    private function registerBrick(ClassificationstoreField $fieldDefinition, string $key = self::BRICK_KEY): void
    {
        $brick = new Definition();
        $brick->setKey($key);
        $brick->setFieldDefinitions([$fieldDefinition->getName() => $fieldDefinition]);

        RuntimeCache::set('objectbrick_' . $key, $brick);
    }
}
