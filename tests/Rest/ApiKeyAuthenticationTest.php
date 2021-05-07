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
