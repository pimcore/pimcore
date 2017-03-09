<?php

namespace Pimcore\Tests\Test;

use Pimcore\Tests\Rest\BrowserKitRestClient;
use Pimcore\Tests\RestTester;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Tool\RestClient;

abstract class RestTestCase extends TestCase
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
     * @var bool
     */
    protected $cleanupInSetup = true;

    /**
     * @var string
     */
    protected $authenticateUser = 'rest';

    /**
     * @inheritDoc
     */
    protected function needsDb()
    {
        return true;
    }

    public function setUp()
    {
        parent::setUp();

        if ($this->cleanupInSetup) {
            // every single rest test assumes a clean database
            TestHelper::cleanUp();
        }

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
