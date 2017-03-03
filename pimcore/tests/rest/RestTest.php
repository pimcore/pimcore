<?php

namespace Pimcore\Tests;

use Pimcore\Tests\Test\TestCase;

class RestTest extends TestCase
{
    /**
     * @var \Pimcore\Tests\RestTester
     */
    protected $tester;

    public function testConnection()
    {
        $this->tester->sendGET('/object/id/1');
        $this->tester->seeResponseCodeIs(403);

        dump($this->tester->grabResponse());
    }
}
