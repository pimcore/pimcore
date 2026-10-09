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

use Pimcore;
use Pimcore\Model\DataObject\CalculatedValueContainer;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick\Data\UnittestBrick;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Helper\DataType\Calculator;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The validation loop and the dependency resolution of a data object must not run the
 * calculator of a CalculatedValue field, as neither of them can do anything with its result.
 * That includes calculated fields of object bricks and field collections.
 */
class CalculatedValueEvaluationTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();

        $this->tester->setupFieldcollection_CalculatedValueItem();
        $this->tester->setupPimcoreClass_CalculatedValueContainer();
    }

    public function testResolveDependenciesDoesNotEvaluateCalculators(): void
    {
        $object = TestHelper::createEmptyObject();

        $this->assertSame(0, $this->countEvaluations(static fn () => $object->resolveDependencies()));
    }

    public function testResolveDependenciesDoesNotEvaluateBrickCalculators(): void
    {
        $object = $this->createUnittestWithBrick();

        $this->assertSame(0, $this->countEvaluations(static fn () => $object->resolveDependencies()));
    }

    public function testResolveDependenciesDoesNotEvaluateFieldcollectionCalculators(): void
    {
        $object = $this->createContainerWithItem();

        $this->assertSame(0, $this->countEvaluations(static fn () => $object->resolveDependencies()));
    }

    public function testSaveEvaluatesCalculatorOnlyForTheQueryTable(): void
    {
        $object = $this->createUnittest();

        // the calculated value is evaluated exactly once per save, to fill the query table
        $this->assertSame(1, $this->countEvaluations(static fn () => $object->save()));
    }

    public function testSaveEvaluatesBrickCalculatorOnlyForTheQueryTable(): void
    {
        $object = $this->createUnittestWithBrick();

        // once for the calculated field of the object, once for the one of the brick
        $this->assertSame(2, $this->countEvaluations(static fn () => $object->save()));
    }

    public function testSaveDoesNotEvaluateFieldcollectionCalculators(): void
    {
        $object = $this->createContainerWithItem();

        $this->assertSame(0, $this->countEvaluations(static fn () => $object->save()));
    }

    /**
     * The version snapshot evaluates calculated values as well, so it is kept out of the measurement.
     */
    private function countEvaluations(callable $action): int
    {
        Version::disable();

        try {
            Calculator::resetEvaluationCount();
            $action();
        } finally {
            Version::enable();
        }

        return Calculator::getEvaluationCount();
    }

    private function createUnittest(): Unittest
    {
        $object = new Unittest();
        $object->setParentId(1);
        $object->setKey(uniqid('calc-eval-'));
        $object->setPublished(true);

        return $object;
    }

    private function createUnittestWithBrick(): Unittest
    {
        $object = $this->createUnittest();
        $object->getMybricks()->setUnittestBrick(new UnittestBrick($object));

        return $object;
    }

    private function createContainerWithItem(): CalculatedValueContainer
    {
        $object = new CalculatedValueContainer();
        $object->setParentId(1);
        $object->setKey(uniqid('calc-eval-'));
        $object->setPublished(true);

        $item = new Fieldcollection\Data\CalculatedValueItem();
        $item->setFieldinput('item');
        $items = new Fieldcollection();
        $items->add($item);
        $object->setItems($items);

        return $object;
    }
}
