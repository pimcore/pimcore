<?php

namespace Pimcore\Tests\Test;

use Codeception\TestCase\Test;
use Pimcore\Tests\Util\TestHelper;

abstract class TestCase extends Test
{
    /**
     * Determine if the test needs a DB connection (will be skipped if no DB is present)
     *
     * @return bool
     */
    protected function needsDb()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        if ($this->needsDb()) {
            TestHelper::checkDbSupport();
        }

        \Pimcore::collectGarbage();
    }
}
