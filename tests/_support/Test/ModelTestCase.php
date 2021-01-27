<?php

namespace Pimcore\Tests\Test;

use Pimcore\Tests\Helper\DataType\Calculator;
use Pimcore\Tests\ModelTester;

/**
 * @property ModelTester $tester
 */
abstract class ModelTestCase extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        \Pimcore::getContainer()->set("test.calculatorservice", new Calculator());

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
     * @inheritdoc
     */
    protected function needsDb()
    {
        return true;
    }
}
