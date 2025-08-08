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

namespace Pimcore\Tests\Support\Test;

use Codeception\Test\Unit;
use Pimcore;
use Pimcore\Tests\Support\Helper\DataType\Calculator;
use Pimcore\Tests\Support\Util\TestHelper;

abstract class TestCase extends Unit
{
    protected bool $cleanupDbInSetup = true;

    /**
     * Determine if the test needs a DB connection (will be skipped if no DB is present)
     *
     */
    protected function needsDb(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::getContainer()->set('test.calculatorservice', new Calculator());

        if ($this->needsDb()) {
            TestHelper::checkDbSupport();

            // every single test assumes a clean database
            if ($this->cleanupDbInSetup) {
                TestHelper::cleanUp();
            }
        }

        Pimcore::collectGarbage();
    }
}
