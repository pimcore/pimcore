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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Helper\Pimcore;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * A reverse relation is the read-only mirror of a relation that is stored - and whose
 * dependency is recorded - on the owning object, which is why
 * ReverseObjectRelation::resolveDependencies() returns an empty array. Dependency resolution
 * must therefore not read the reverse relation at all: its getter is not memoised, so every
 * call queries the relation table and loads every owning object.
 *
 * @group model.dataobject.reverseobjectrelation
 */
class ReverseObjectRelationDependencyTest extends ModelTestCase
{
    private DebugDataHolder $debugDataHolder;

    protected function setUp(): void
    {
        parent::setUp();

        TestHelper::cleanUp();

        $container = $this->getModule('\\'.Pimcore::class)->_getContainer();
        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('The Doctrine debug data holder is only registered when the kernel runs in debug mode.');
        }

        $this->debugDataHolder = $container->get('doctrine.debug_data_holder');
    }

    protected function tearDown(): void
    {
        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * The unittest class relates to itself: `objects` is the owning side and `nonowner` is
     * its reverse relation, so one owner and one target of the same class are enough.
     */
    public function testResolveDependenciesDoesNotLoadTheReverseRelation(): void
    {
        $target = TestHelper::createEmptyObject('reverse-relation-target-');
        $owner = TestHelper::createEmptyObject('reverse-relation-owner-', false);
        $owner->setObjects([$target]);
        $owner->save();

        $target = Unittest::getById($target->getId(), ['force' => true]);

        // precondition: reading the reverse relation is what issues the query this test
        // guards against, so the pattern below has to catch it
        $this->debugDataHolder->reset();
        $related = $target->getNonowner();
        $this->assertCount(1, $related);
        $this->assertSame($owner->getId(), $related[0]->getId());
        $this->assertNotEmpty($this->grabReverseRelationReads(), 'precondition: the getter reads the reverse relation');

        $this->debugDataHolder->reset();
        $dependencies = $target->resolveDependencies();

        $this->assertSame(
            [],
            $this->grabReverseRelationReads(),
            'resolving the dependencies of the target must not read the reverse relation'
        );

        // the dependency belongs to the owning side of the relation, which still records it
        $this->assertArrayNotHasKey('object_'.$owner->getId(), $dependencies);
        $this->assertArrayHasKey('object_'.$target->getId(), $owner->resolveDependencies());
    }

    /**
     * @return string[]
     */
    private function grabReverseRelationReads(): array
    {
        $reads = [];
        foreach ($this->debugDataHolder->getData() as $queries) {
            foreach ($queries as $query) {
                $sql = preg_replace('/\s+/', ' ', (string)$query['sql']);
                if (preg_match('/^SELECT \* FROM object_relations_\S+ WHERE dest_id = \?/i', $sql)) {
                    $reads[] = $sql;
                }
            }
        }

        return $reads;
    }
}
