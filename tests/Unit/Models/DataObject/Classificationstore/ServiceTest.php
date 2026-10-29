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
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Tests\Support\Test\TestCase;

class ServiceTest extends TestCase
{
    private const BRICK_KEY = 'csEngineBrick';

    private const OTHER_BRICK_KEY = 'csEngineOtherBrick';

    /**
     * Object bricks and field collections are keyed in separate name spaces, so one key can name
     * both - which is exactly the case the qualified identity has to keep apart.
     */
    private const SHARED_KEY = 'csEngineShared';

    protected function tearDown(): void
    {
        foreach ([self::BRICK_KEY, self::OTHER_BRICK_KEY, self::SHARED_KEY] as $brickKey) {
            RuntimeCache::getInstance()->offsetUnset('objectbrick_' . $brickKey);
        }

        RuntimeCache::getInstance()->offsetUnset('fieldcollection_' . self::SHARED_KEY);

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

        $this->assertSame(['objectbrick:' . self::BRICK_KEY . '.csInBrick'], array_keys($collected));
        $this->assertSame(5, Service::getConfiguredStoreId($collected['objectbrick:' . self::BRICK_KEY . '.csInBrick']));
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
            ['objectbrick:' . self::BRICK_KEY . '.cs', 'objectbrick:' . self::OTHER_BRICK_KEY . '.cs'],
            array_keys($collected)
        );
        $this->assertSame(5, Service::getConfiguredStoreId($collected['objectbrick:' . self::BRICK_KEY . '.cs']));
        $this->assertSame(6, Service::getConfiguredStoreId($collected['objectbrick:' . self::OTHER_BRICK_KEY . '.cs']));
    }

    /**
     * Object brick keys and field collection keys live in separate name spaces, so a brick and a
     * field collection may both be called the same thing and both contain a "cs". Qualifying the
     * identity by key alone gave them one and the same key, which dropped one of the two
     * definitions - and with it the engine of the store behind it.
     */
    public function testABrickAndAFieldCollectionSharingAKeyAreBothCollected(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5), self::SHARED_KEY);
        $this->registerFieldCollection($this->classificationStoreField('cs', 6), self::SHARED_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::SHARED_KEY]);

        $fieldCollections = new Fieldcollections();
        $fieldCollections->setName('fieldcollections');
        $fieldCollections->setAllowedTypes([self::SHARED_KEY]);

        $collected = Service::getClassificationStoreFieldDefinitions([
            'bricks' => $bricks,
            'fieldcollections' => $fieldCollections,
        ]);

        $this->assertSame(
            ['objectbrick:' . self::SHARED_KEY . '.cs', 'fieldcollection:' . self::SHARED_KEY . '.cs'],
            array_keys($collected)
        );
        $this->assertSame(5, Service::getConfiguredStoreId($collected['objectbrick:' . self::SHARED_KEY . '.cs']));
        $this->assertSame(6, Service::getConfiguredStoreId($collected['fieldcollection:' . self::SHARED_KEY . '.cs']));
    }

    /**
     * Both definitions have to reach the group the engine is resolved from, or the store of the one
     * that was dropped is never checked against the other's.
     */
    public function testTheStoresOfABrickAndAFieldCollectionSharingAKeyAreBothGroupedUnderTheFieldName(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5), self::SHARED_KEY);
        $this->registerFieldCollection($this->classificationStoreField('cs', 6), self::SHARED_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::SHARED_KEY]);

        $fieldCollections = new Fieldcollections();
        $fieldCollections->setName('fieldcollections');
        $fieldCollections->setAllowedTypes([self::SHARED_KEY]);

        $this->assertSame(
            [
                'cs' => [
                    'objectbrick:' . self::SHARED_KEY . '.cs' => 5,
                    'fieldcollection:' . self::SHARED_KEY . '.cs' => 6,
                ],
            ],
            Service::groupStoreIdsByFieldName(Service::getClassificationStoreFieldDefinitions([
                'bricks' => $bricks,
                'fieldcollections' => $fieldCollections,
            ]))
        );
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
                'cs' => [
                    'objectbrick:' . self::BRICK_KEY . '.cs' => 5,
                    'objectbrick:' . self::OTHER_BRICK_KEY . '.cs' => 6,
                ],
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

    /**
     * Fieldcollection\Definition::getByKey() answers from the runtime cache the same way
     * {@see registerBrick()} relies on, under its own cache key prefix.
     */
    private function registerFieldCollection(
        ClassificationstoreField $fieldDefinition,
        string $key = self::SHARED_KEY
    ): void {
        $fieldCollection = new FieldcollectionDefinition();
        $fieldCollection->setKey($key);
        $fieldCollection->setFieldDefinitions([$fieldDefinition->getName() => $fieldDefinition]);

        RuntimeCache::set('fieldcollection_' . $key, $fieldCollection);
    }
}
