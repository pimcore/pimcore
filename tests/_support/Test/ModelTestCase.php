<?php

namespace Pimcore\Tests\Test;

use Pimcore\Tests\ModelTester;

/**
 * @property ModelTester $tester
 */
abstract class ModelTestCase extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

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
