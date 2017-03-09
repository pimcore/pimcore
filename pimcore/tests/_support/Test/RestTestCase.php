<?php

namespace Pimcore\Tests\Test;

use Pimcore\Tests\Test\Traits\RestTestCaseTrait;

abstract class RestTestCase extends TestCase
{
    use RestTestCaseTrait;

    /**
     * @inheritDoc
     */
    protected function needsDb()
    {
        return true;
    }
}
