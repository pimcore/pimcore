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

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Classificationstore\RelationEditModeResolver;
use Pimcore\Model\DataObject\Classificationstore\RelationResolver;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Which keys the edit mode payload renders from their stored descriptors instead of resolving them.
 *
 * The rendering reproduces five shapes it does not own, so it must claim exactly those five: a type
 * that extends one of them may render its rows differently, and answering `true` for it would hand
 * the object editor its parent's shape. The cheaper path is worth nothing if it is also the wrong
 * one, so the fallback is what an unlisted type gets.
 */
class RelationEditModeResolverTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function renderedTypeProvider(): array
    {
        return [
            'manyToOneRelation' => [ManyToOneRelation::class],
            'manyToManyRelation' => [ManyToManyRelation::class],
            'manyToManyObjectRelation' => [ManyToManyObjectRelation::class],
            'advancedManyToManyRelation' => [AdvancedManyToManyRelation::class],
            'advancedManyToManyObjectRelation' => [AdvancedManyToManyObjectRelation::class],
        ];
    }

    /**
     * @dataProvider renderedTypeProvider
     */
    public function testEveryRelationTypeCoreShipsIsRendered(string $className): void
    {
        $this->assertTrue($this->resolver()->supports(new $className()));
    }

    public function testAProjectTypeExtendingARenderedOneIsNotRendered(): void
    {
        $this->assertFalse(
            $this->resolver()->supports(new RelationEditModeResolverTestProjectRelation()),
            'a subclass may render its rows differently, so it has to keep its own getDataForEditmode()'
        );
    }

    public function testAKeyThatIsNoRelationIsNotRendered(): void
    {
        $this->assertFalse($this->resolver()->supports(new Input()));
    }

    /**
     * A computed relation is refused before it is ever stored, so the payload never meets one.
     */
    public function testAComputedRelationIsNotRendered(): void
    {
        $this->assertFalse($this->resolver()->supports(new ReverseObjectRelation()));
    }

    public function testNoDefinitionIsNotRendered(): void
    {
        $this->assertFalse($this->resolver()->supports(null));
    }

    private function resolver(): RelationEditModeResolver
    {
        return new RelationEditModeResolver(new RelationResolver($this->createMock(Connection::class)));
    }
}

/**
 * A project relation type, as a bundle that extends a core one would declare it.
 */
class RelationEditModeResolverTestProjectRelation extends ManyToManyObjectRelation
{
}
