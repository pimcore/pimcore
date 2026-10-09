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
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Helper\DataType\Calculator;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The validation loop and the dependency resolution of a data object must not run the
 * calculator of a CalculatedValue field, as neither of them can do anything with its result.
 */
class CalculatedValueEvaluationTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
    }

    public function testResolveDependenciesDoesNotEvaluateCalculators(): void
    {
        $object = TestHelper::createEmptyObject();

        $this->resetEvaluationCounter();
        $object->resolveDependencies();

        $this->assertSame(0, $this->getEvaluationCount());
    }

    public function testSaveEvaluatesCalculatorOnlyForTheQueryTable(): void
    {
        $object = new Unittest();
        $object->setParentId(1);
        $object->setKey(uniqid('calc-eval-'));
        $object->setPublished(true);

        // the version snapshot evaluates the calculated value as well, keep it out of the measurement
        Version::disable();

        try {
            $this->resetEvaluationCounter();
            $object->save();
        } finally {
            Version::enable();
        }

        // the calculated value is evaluated exactly once per save, to fill the query table
        $this->assertSame(1, $this->getEvaluationCount());
    }

    private function resetEvaluationCounter(): void
    {
        RuntimeCache::set(Calculator::EVALUATION_COUNTER, 0);
    }

    private function getEvaluationCount(): int
    {
        return (int)RuntimeCache::get(Calculator::EVALUATION_COUNTER);
    }
}
