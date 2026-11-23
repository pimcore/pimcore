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
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Dao;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class DaoTest extends TestCase
{
    private const BRICK_KEY = 'csEngineDaoBrick';

    private const OTHER_BRICK_KEY = 'csEngineDaoOtherBrick';

    protected function tearDown(): void
    {
        foreach ([self::BRICK_KEY, self::OTHER_BRICK_KEY] as $brickKey) {
            RuntimeCache::getInstance()->offsetUnset('objectbrick_' . $brickKey);
        }

        parent::tearDown();
    }

    /**
     * The model publishes these as magic methods resolved by AbstractModel::__call, so they stay
     * public and callable without arguments no matter which engine runs behind them.
     */
    public function testThePublishedDaoMethodsKeepTheirNameAndArity(): void
    {
        $reflection = new ReflectionClass(Dao::class);

        foreach (['createUpdateTable', 'delete', 'load', 'save', 'getDataTableName', 'getGroupsTableName'] as $name) {
            $this->assertTrue($reflection->hasMethod($name), $name . '() must stay on the Dao');

            $method = $reflection->getMethod($name);
            $this->assertTrue($method->isPublic(), $name . '() must stay public');
            $this->assertSame(0, $method->getNumberOfRequiredParameters(), $name . '() must take no required argument');
        }
    }

    public function testCreateUpdateTableKeepsItsSingularNameAndVoidReturn(): void
    {
        $method = new ReflectionMethod(Dao::class, 'createUpdateTable');
        $returnType = $method->getReturnType();

        $this->assertSame(0, $method->getNumberOfParameters());
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame('void', $returnType->getName());
        $this->assertFalse((new ReflectionClass(Dao::class))->hasMethod('createUpdateTables'));
    }

    /**
     * createUpdateTable() is reachable on a model that carries only its class - the model's
     * fieldname is a typed property with no default, so resolving the store must not read it blindly.
     */
    public function testTheStoreIsNotResolvedFromAnUnsetFieldname(): void
    {
        $this->assertSame([], $this->resolveStoreIds($this->storeFor(null)));
    }

    public function testTheStoreIsResolvedFromTheFieldDefinitionOfTheFieldname(): void
    {
        $this->assertSame(['csConfigured' => 9], $this->resolveStoreIds($this->storeFor('csConfigured')));
    }

    /**
     * A class definition without a storeId key leaves the typed property uninitialized, which means
     * "no store configured" and must not be mistaken for store 1.
     */
    public function testAFieldWithoutAStoreResolvesToNoStore(): void
    {
        $this->assertSame(['csUnconfigured' => null], $this->resolveStoreIds($this->storeFor('csUnconfigured')));
    }

    /**
     * A classification store nested in an object brick is reachable from the owning class, so the
     * facade has to resolve that field's store the same way it resolves a top-level one.
     */
    public function testAFieldNestedInABrickResolvesToItsOwnStore(): void
    {
        $this->assertSame(
            ['objectbrick:' . self::BRICK_KEY . '.csInBrick' => 11],
            $this->resolveStoreIds($this->storeFor('csInBrick'))
        );
    }

    /**
     * Two bricks allowed on the same class may define a classification store under the same name,
     * on different stores. The name identifies a group rather than one definition, so every store
     * behind it reaches the resolver instead of the first one silently winning.
     */
    public function testEveryStoreBehindASharedFieldNameIsResolved(): void
    {
        $this->assertSame(
            [
                'objectbrick:' . self::BRICK_KEY . '.csShared' => 12,
                'objectbrick:' . self::OTHER_BRICK_KEY . '.csShared' => 13,
            ],
            $this->resolveStoreIds($this->storeFor('csShared'))
        );
    }

    public function testTheDaoStaysInternal(): void
    {
        $this->assertStringContainsString('@internal', (string) (new ReflectionClass(Dao::class))->getDocComment());
    }

    public function testTheModelStillPublishesTheSingularCreateUpdateTable(): void
    {
        $docComment = (string) (new ReflectionClass(Classificationstore::class))->getDocComment();

        $this->assertStringContainsString(
            'Pimcore\Model\DataObject\Classificationstore\Dao createUpdateTable()',
            $docComment
        );
    }

    private function storeFor(?string $fieldname): Classificationstore
    {
        $classificationStore = new Classificationstore();
        $classificationStore->setClass($this->classDefinition());

        if ($fieldname !== null) {
            $classificationStore->setFieldname($fieldname);
        }

        return $classificationStore;
    }

    private function classDefinition(): ClassDefinition
    {
        $configured = new ClassificationstoreField();
        $configured->setName('csConfigured');
        $configured->setStoreId(9);

        $unconfigured = new ClassificationstoreField();
        $unconfigured->setName('csUnconfigured');

        $nested = new ClassificationstoreField();
        $nested->setName('csInBrick');
        $nested->setStoreId(11);

        $this->registerBrick(self::BRICK_KEY, ['csInBrick' => $nested, 'csShared' => $this->sharedField(12)]);
        $this->registerBrick(self::OTHER_BRICK_KEY, ['csShared' => $this->sharedField(13)]);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::BRICK_KEY, self::OTHER_BRICK_KEY]);

        $class = new ClassDefinition();
        $class->setId('UNIT');
        $class->setName('unit');
        $class->setFieldDefinitions([
            'csConfigured' => $configured,
            'csUnconfigured' => $unconfigured,
            'bricks' => $bricks,
        ]);

        return $class;
    }

    private function sharedField(int $storeId): ClassificationstoreField
    {
        $fieldDefinition = new ClassificationstoreField();
        $fieldDefinition->setName('csShared');
        $fieldDefinition->setStoreId($storeId);

        return $fieldDefinition;
    }

    /**
     * @param array<string, ClassificationstoreField> $fieldDefinitions
     */
    private function registerBrick(string $key, array $fieldDefinitions): void
    {
        $brick = new Definition();
        $brick->setKey($key);
        $brick->setFieldDefinitions($fieldDefinitions);

        RuntimeCache::set('objectbrick_' . $key, $brick);
    }

    /**
     * @return array<string, int|null>
     */
    private function resolveStoreIds(Classificationstore $classificationStore): array
    {
        $dao = (new ReflectionClass(Dao::class))->newInstanceWithoutConstructor();
        $dao->setModel($classificationStore);

        $method = new ReflectionMethod(Dao::class, 'getStoreIds');
        $method->setAccessible(true);

        return $method->invoke($dao);
    }
}
