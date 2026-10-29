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
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

class EngineResolverTest extends TestCase
{
    private const STORE_ID = 7;

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
}
