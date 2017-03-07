<?php

namespace Pimcore\Tests\Test;

use Pimcore\Tests\Rest\RestClient;
use Pimcore\Tests\RestTester;
use Pimcore\Tests\Util\TestHelper;

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

    public function setUp()
    {
        parent::setUp();

        if ($this->cleanupInSetup) {
            // every single rest test assumes a clean database
            TestHelper::cleanUp();
        }

        if ($this->authenticateUser) {
            // authenticate as rest user
            $this->tester->addApiKeyParam($this->authenticateUser);
        }

        // setup test rest client
        $this->restClient = new RestClient($this->tester);
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
