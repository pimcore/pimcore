<?php

namespace Pimcore\Tests\Test\Traits;

use Pimcore\Tests\Rest\BrowserKitRestClient;
use Pimcore\Tests\RestTester;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tool\RestClient;

trait RestTestCaseTrait
{
    /**
     * @var RestTester
     */
    protected $tester;

    /**
     * @var RestClient
     */
    protected $restClient;

    /**
     * @var string
     */
    protected $authenticateUser = 'rest';

    public function setUp()
    {
        /** @var $this TestCase */
        parent::setUp();

        // setup test rest client
        $this->restClient = new BrowserKitRestClient($this->tester->getHttpClient());

        // authenticate as rest user
        if ($this->authenticateUser) {
            $this->restClient->setApiKey($this->tester->getRestApiKey($this->authenticateUser));
        }
    }

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
