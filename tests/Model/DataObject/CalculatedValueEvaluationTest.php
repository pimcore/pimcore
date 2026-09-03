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
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Support\Helper\DataType\Calculator;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * A calculated value is computed on read and never persisted from user input, and both
 * CalculatedValue::checkValidity() and the inherited Data::resolveDependencies() ignore
 * the value they are handed. Save validation and dependency resolution must therefore not
 * run the calculator at all - neither for a field on the class itself nor for one owned by
 * a container such as an object brick.
 *
 * @group model.dataobject.calculatedvalue
 */
class CalculatedValueEvaluationTest extends ModelTestCase
{
    private const CALCULATED = 'computed-value';

    protected function setUp(): void
    {
        parent::setUp();

        TestHelper::cleanUp();

        // the test calculator returns this runtime-cache value for every calculated field
        RuntimeCache::set('modeltest.testCalculatedValue.value', self::CALCULATED);
        Calculator::$computeCount = 0;
    }

    protected function tearDown(): void
    {
        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * The inheritance class carries a calculated value of its own and one inside its
     * localized fields, and its unittestBrick carries a third one - so a single object
     * exercises the class-level loop in Concrete::update() as well as the container-owned
     * loops in Objectbricks and Localizedfields.
     */
    private function createObjectWithCalculatedValues(): Inheritance
    {
        $object = new Inheritance();
        $object->setKey('calculated-value-evaluation');
        $object->setParentId(1);
        $object->setPublished(true);
        $object->setNormalInput('some text');

        $brick = new DataObject\Objectbrick\Data\UnittestBrick($object);
        $brick->setBrickInput('brick-input');
        $object->getMybricks()->setUnittestBrick($brick);

        return $object;
    }

    public function testSaveValidationDoesNotEvaluateCalculators(): void
    {
        $object = $this->createObjectWithCalculatedValues();

        $dispatcher = Pimcore::getEventDispatcher();

        // PRE_ADD/PRE_UPDATE are dispatched before Concrete::update() runs its validation
        // loop and PRE_UPDATE_VALIDATION_EXCEPTION immediately after it, so the difference
        // between the two isolates exactly the calculator evaluations made while validating.
        $countWhenValidationStarted = null;
        $countWhenValidationFinished = null;

        $start = static function () use (&$countWhenValidationStarted): void {
            $countWhenValidationStarted = Calculator::$computeCount;
        };
        $finish = static function () use (&$countWhenValidationFinished): void {
            $countWhenValidationFinished = Calculator::$computeCount;
        };

        $dispatcher->addListener(DataObjectEvents::PRE_ADD, $start);
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE, $start);
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $finish);

        try {
            $object->save();
        } finally {
            $dispatcher->removeListener(DataObjectEvents::PRE_ADD, $start);
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE, $start);
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $finish);
        }

        $this->assertNotNull($countWhenValidationStarted, 'the save dispatched a pre-save event');
        $this->assertNotNull($countWhenValidationFinished, 'the save dispatched the validation event');
        $this->assertSame(
            0,
            $countWhenValidationFinished - $countWhenValidationStarted,
            'save validation must not evaluate calculated values, neither on the class nor in a brick'
        );
    }

    public function testResolveDependenciesDoesNotEvaluateCalculators(): void
    {
        $object = $this->createObjectWithCalculatedValues();
        $object->save();

        Calculator::$computeCount = 0;
        $object->resolveDependencies();

        $this->assertSame(
            0,
            Calculator::$computeCount,
            'dependency resolution must not evaluate calculated values, neither on the class nor in a brick'
        );
    }

    /**
     * The object brick container on its own, so that a regression is attributed to the
     * container rather than to the object save as a whole.
     */
    public function testObjectbrickContainerDoesNotEvaluateCalculators(): void
    {
        $object = $this->createObjectWithCalculatedValues();
        $object->save();

        $fieldDefinition = $object->getClass()->getFieldDefinition('mybricks');
        $this->assertInstanceOf(Objectbricks::class, $fieldDefinition);

        $bricks = $object->getMybricks();

        Calculator::$computeCount = 0;
        $fieldDefinition->checkValidity($bricks, false, []);
        $this->assertSame(0, Calculator::$computeCount, 'Objectbricks::checkValidity() must not evaluate calculated values');

        Calculator::$computeCount = 0;
        $fieldDefinition->resolveDependencies($bricks);
        $this->assertSame(0, Calculator::$computeCount, 'Objectbricks::resolveDependencies() must not evaluate calculated values');
    }

    /**
     * Skipping the two consumers that discard the value must not change what is persisted:
     * the query tables are still filled from the calculator during the save.
     */
    public function testCalculatedValuesAreStillPersisted(): void
    {
        $object = $this->createObjectWithCalculatedValues();
        $object->save();

        $db = Db::get();
        $classId = $object->getClassId();

        $row = $db->fetchAssociative(
            'SELECT * FROM object_query_' . $classId . ' WHERE oo_id = ?',
            [$object->getId()]
        );
        $this->assertSame(self::CALCULATED, $row['calculatedinherited']);

        $localizedRow = $db->fetchAssociative(
            'SELECT * FROM object_localized_query_' . $classId . '_en WHERE ooo_id = ?',
            [$object->getId()]
        );
        $this->assertSame(self::CALCULATED, $localizedRow['lcalculatedinherited']);

        $brickRow = $db->fetchAssociative(
            'SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?',
            [$object->getId()]
        );
        $this->assertSame(self::CALCULATED, $brickRow['brickcalculated']);

        // and the getters keep working after the save
        $reloaded = Inheritance::getById($object->getId(), ['force' => true]);
        $this->assertSame(self::CALCULATED, $reloaded->getCalculatedinherited());
        $this->assertSame(self::CALCULATED, $reloaded->getMybricks()->getUnittestBrick()->getBrickcalculated());
    }
}
