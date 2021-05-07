<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Test\Traits;

use Pimcore\Tests\RestClient\BrowserKitRestClient;
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
        /** @var TestCase $this */
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
