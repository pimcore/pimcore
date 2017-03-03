<?php

namespace Pimcore\Tests\Test;

abstract class RestTestCase extends TestCase
{
    /**
     * Params which will be added to each request
     *
     * @return array
     */
    public function getGlobalRequestParams()
    {
        return [];
    }
}
