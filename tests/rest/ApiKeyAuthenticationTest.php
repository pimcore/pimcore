<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Tests\Test\RestTestCase;

class ApiKeyAuthenticationTest extends RestTestCase
{
    protected $authenticateUser = false;

    public function testUnauthenticatedConnection()
    {
        $this->tester->sendGET('/user');
        $this->tester->seeResponseCodeIs(403);

        $this->tester->canSeeResponseIsJson();
        $this->tester->canSeeResponseContainsJson(['success' => false]);
    }

    public function testAuthenticatedConnection()
    {
        $username = 'rest';

        $this->tester->addApiKeyParam($username);

        $this->tester->sendGET('/user');
        $this->tester->seeResponseCodeIs(200);

        $this->tester->canSeeResponseIsJson();
        $this->tester->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'type' => 'user',
                'admin' => true,
                'active' => true,
                'name' => $username,
            ],
        ]);
    }
}
