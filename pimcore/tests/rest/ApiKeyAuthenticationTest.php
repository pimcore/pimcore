<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Tests\Test\TestCase;

class ApiKeyAuthenticationTest extends TestCase
{
    /**
     * @var \Pimcore\Tests\RestTester
     */
    protected $tester;

    public function testUnauthenticatedConnection()
    {
        $this->tester->sendGET('/info/user');
        $this->tester->seeResponseCodeIs(403);

        $this->tester->canSeeResponseIsJson();
        $this->tester->canSeeResponseContainsJson(['success' => false]);
    }

    public function testAuthenticatedConnection()
    {
        $username = 'rest';

        $this->tester->addApiKeyParam($username);

        $this->tester->sendGET('/info/user');
        $this->tester->seeResponseCodeIs(200);

        $this->tester->canSeeResponseIsJson();
        $this->tester->canSeeResponseContainsJson([
            'success' => true,
            'user'    => $username
        ]);
    }
}
