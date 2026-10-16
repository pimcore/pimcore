<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Security;

use Pimcore;
use Pimcore\Bundle\GenericExecutionEngineBundle\Security\PermissionService;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PermissionServiceTest extends TestCase
{
    private RequestStack $requestStack;

    private int $sessionReads = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn('PHPSESSID');
        $session->method('get')->willReturnCallback(function (): mixed {
            $this->sessionReads++;

            return null;
        });

        $request = new Request();
        $request->cookies->set('PHPSESSID', 'previous-session');
        $request->setSession($session);

        $this->requestStack = Pimcore::getContainer()->get('request_stack');
        $this->requestStack->push($request);
    }

    protected function tearDown(): void
    {
        $this->requestStack->pop();

        parent::tearDown();
    }

    /**
     * Building the service (as a dependency of JobRunStatusChangeSubscriber on any entity's
     * preUpdate) must not read the session, which would start it after a streamed response
     * has sent its headers.
     */
    public function testConstructingDoesNotReadTheSession(): void
    {
        new PermissionService();

        $this->assertSame(0, $this->sessionReads);
    }

    public function testUserIsResolvedOnceOnFirstPermissionCheck(): void
    {
        $service = new PermissionService();

        $this->assertFalse($service->isAllowedToSeeJobRuns());
        $this->assertFalse($service->isAllowedToSeeAllJobRuns());
        $this->assertSame(1, $this->sessionReads);
    }
}
