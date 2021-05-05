<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Test;

use Pimcore\Tests\Helper\DataType\Calculator;
use Pimcore\Tests\ModelTester;

/**
 * @property ModelTester $tester
 */
abstract class ModelTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        \Pimcore::getContainer()->set('test.calculatorservice', new Calculator());

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }
    }

    /**
     * Set up test classes before running tests
     */
    protected function setUpTestClasses()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function needsDb()
    {
        return true;
    }
}
