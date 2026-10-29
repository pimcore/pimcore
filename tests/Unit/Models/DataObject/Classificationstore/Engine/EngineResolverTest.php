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

namespace Pimcore\Tests\Unit\Model\DataObject\Classificationstore\Engine;

use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Exception\ClassificationStoreEngineNotAvailableException;
use Pimcore\Model\DataObject\Exception\ConflictingClassificationStoreEnginesException;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionProperty;

class EngineResolverTest extends TestCase
{
    private const STORE_ID = 7;

    private const OTHER_STORE_ID = 8;

    /**
     * No row of classificationstore_stores can carry this id, so resolving it exercises the
     * "store is not configured at all" path without seeding one.
     */
    private const UNKNOWN_STORE_ID = 2147483647;

    public function testNullResolvesToTheDefaultEngine(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);

        $this->assertSame($eavEngine, $this->resolver($eavEngine)->resolve(null, self::STORE_ID));
    }

    public function testTheExplicitDefaultNameResolvesToTheDefaultEngine(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);

        $this->assertSame($eavEngine, $this->resolver($eavEngine)->resolve('eav', self::STORE_ID));
    }

    public function testAStoreWithoutARowResolvesToTheDefaultEngine(): void
    {
        // the only case here that reads the store configuration back
        TestHelper::checkDbSupport();

        $eavEngine = $this->createMock(EngineInterface::class);

        $this->assertSame(
            $eavEngine,
            $this->resolver($eavEngine)->resolveForStore(self::UNKNOWN_STORE_ID)
        );
    }

    public function testNoStoreAtAllResolvesToTheDefaultEngine(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);

        $this->assertSame($eavEngine, $this->resolver($eavEngine)->resolveForStore(null));
    }

    public function testAnUnresolvableValueThrowsInsteadOfFallingBackToTheDefaultEngine(): void
    {
        $this->expectException(UnknownClassificationStoreEngineException::class);
        $this->expectExceptionMessage('Classification store 7 is configured for the storage engine "nosuchengine", '
            . 'which is not registered. Set classificationstore_stores.engine of store 7 back to NULL to use the '
            . 'default engine, or install the bundle that provides "nosuchengine".');

        $this->resolver($this->createMock(EngineInterface::class))->resolve('nosuchengine', self::STORE_ID);
    }

    public function testTheNotYetAvailableDocumentEngineThrowsItsOwnError(): void
    {
        $this->expectException(ClassificationStoreEngineNotAvailableException::class);
        $this->expectExceptionMessage('Classification store 7 is configured for the storage engine "document", which'
            . ' this Pimcore version does not ship yet. Set classificationstore_stores.engine of store 7 back to NULL'
            . ' to use the default engine until the bundle providing "document" is available.');

        $this->resolver($this->createMock(EngineInterface::class))->resolve('document', self::STORE_ID);
    }

    /**
     * A group with no store configured at all is the default engine, the same as a single one.
     */
    public function testAGroupOfFieldsWithoutAStoreResolvesToTheDefaultEngine(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $resolver = $this->resolver($eavEngine);

        $this->assertSame($eavEngine, $resolver->resolveForStores([]));
        $this->assertSame($eavEngine, $resolver->resolveForStores(['brickA.cs' => null, 'brickB.cs' => null]));
    }

    /**
     * The value tables key their rows by (object, fieldname), so two containers defining the same
     * classification store field name share their rows and cannot be split across two engines.
     */
    public function testAFieldNameSittingOnTwoEnginesIsRefusedInsteadOfPickingTheFirst(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $otherEngine = $this->createMock(EngineInterface::class);
        $resolver = new EngineResolver(['eav' => $eavEngine, 'other' => $otherEngine]);

        // seeded straight into the memo: the conflict is about the group, not about reading a store
        $this->seedResolvedEngines($resolver, [self::STORE_ID => $eavEngine, self::OTHER_STORE_ID => $otherEngine]);

        $this->expectException(ConflictingClassificationStoreEnginesException::class);
        $this->expectExceptionMessage('brickA.cs uses "eav", brickB.cs uses "other"');

        $resolver->resolveForStores(['brickA.cs' => self::STORE_ID, 'brickB.cs' => self::OTHER_STORE_ID]);
    }

    public function testAFieldNameOnSeveralStoresOfOneEngineResolvesToThatEngine(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $resolver = $this->resolver($eavEngine);

        $this->seedResolvedEngines($resolver, [self::STORE_ID => $eavEngine, self::OTHER_STORE_ID => $eavEngine]);

        $this->assertSame(
            $eavEngine,
            $resolver->resolveForStores(['brickA.cs' => self::STORE_ID, 'brickB.cs' => self::OTHER_STORE_ID])
        );
    }

    /**
     * The resolver outlives a request in a messenger worker, so the memo of which engine a store
     * uses has to be droppable - entry by entry for the code that writes a store, and wholesale at
     * the request and message boundary.
     */
    public function testTheResolvedEnginesAreDroppedByResetAndByInvalidation(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $otherEngine = $this->createMock(EngineInterface::class);
        $resolver = new EngineResolver(['eav' => $eavEngine, 'other' => $otherEngine]);

        $this->seedResolvedEngines($resolver, [self::STORE_ID => $otherEngine, self::OTHER_STORE_ID => $otherEngine]);

        $resolver->invalidateStore(self::STORE_ID);
        $this->assertSame([self::OTHER_STORE_ID => $otherEngine], $this->resolvedEngines($resolver));

        $resolver->reset();
        $this->assertSame([], $this->resolvedEngines($resolver));

        // the registered engines come from the container and survive both
        $this->assertSame(['eav' => $eavEngine, 'other' => $otherEngine], $resolver->getEngines());
    }

    public function testEveryRegisteredEngineIsReturnedByItsName(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $otherEngine = $this->createMock(EngineInterface::class);
        $resolver = new EngineResolver(['eav' => $eavEngine, 'other' => $otherEngine]);

        $this->assertSame(['eav' => $eavEngine, 'other' => $otherEngine], $resolver->getEngines());
        $this->assertSame($otherEngine, $resolver->resolve('other', self::STORE_ID));
    }

    private function resolver(EngineInterface $eavEngine): EngineResolver
    {
        return new EngineResolver(['eav' => $eavEngine]);
    }

    /**
     * Fills the memo resolveForStore() keeps, so that grouping and invalidation can be exercised
     * without a store configuration row behind every id.
     *
     * @param array<int, EngineInterface> $storeEngines
     */
    private function seedResolvedEngines(EngineResolver $resolver, array $storeEngines): void
    {
        (new ReflectionProperty(EngineResolver::class, 'storeEngines'))->setValue($resolver, $storeEngines);
    }

    /**
     * @return array<int, EngineInterface>
     */
    private function resolvedEngines(EngineResolver $resolver): array
    {
        return (new ReflectionProperty(EngineResolver::class, 'storeEngines'))->getValue($resolver);
    }
}
