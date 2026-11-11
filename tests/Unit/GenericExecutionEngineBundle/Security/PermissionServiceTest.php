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
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Security\PermissionService;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\PermissionConstants;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class PermissionServiceTest extends TestCase
{
    private RequestStack $requestStack;

    private int $sessionReads = 0;

    /**
     * Serialized security token the session returns for "_security_pimcore_admin", null for an
     * anonymous session.
     */
    private ?string $sessionToken = null;

    private ?User $createdUser = null;

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn('PHPSESSID');
        $session->method('get')->willReturnCallback(function (string $name): mixed {
            $this->sessionReads++;

            return $name === '_security_pimcore_admin' ? $this->sessionToken : null;
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
        $this->createdUser?->delete();

        parent::tearDown();
    }

    /**
     * Building the service (as a dependency of JobRunStatusChangeSubscriber on any entity's
     * preUpdate) must not read the session, which would start it after a streamed response
     * has sent its headers.
     */
    public function testConstructingDoesNotReadTheSession(): void
    {
        $this->logIn($this->createUser([PermissionConstants::GEE_JOB_RUN]));

        new PermissionService();

        $this->assertSame(0, $this->sessionReads);
    }

    public function testUserIsResolvedOnceOnFirstPermissionCheck(): void
    {
        $this->logIn($this->createUser([PermissionConstants::GEE_JOB_RUN]));
        $service = new PermissionService();

        $service->isAllowedToSeeJobRuns();
        $service->isAllowedToSeeAllJobRuns();
        $service->allowedToSeeJobRuns();

        $this->assertSame(1, $this->sessionReads);
    }

    public function testUserWithJobRunPermissionMaySeeOwnJobRunsOnly(): void
    {
        $this->logIn($this->createUser([PermissionConstants::GEE_JOB_RUN]));
        $service = new PermissionService();

        $this->assertTrue($service->isAllowedToSeeJobRuns());
        $this->assertFalse($service->isAllowedToSeeAllJobRuns());
        $service->allowedToSeeJobRuns();

        $this->expectException(PermissionException::class);
        $service->allowedToSeeAllJobRuns();
    }

    public function testUserWithBothPermissionsMaySeeAllJobRuns(): void
    {
        $this->logIn($this->createUser([PermissionConstants::GEE_JOB_RUN, PermissionConstants::GEE_SEE_ALL_JOB_RUNS]));
        $service = new PermissionService();

        $this->assertTrue($service->isAllowedToSeeJobRuns());
        $this->assertTrue($service->isAllowedToSeeAllJobRuns());
        $service->allowedToSeeJobRuns();
        $service->allowedToSeeAllJobRuns();
    }

    public function testUserWithoutPermissionsIsDenied(): void
    {
        $this->logIn($this->createUser([]));
        $service = new PermissionService();

        $this->assertFalse($service->isAllowedToSeeJobRuns());
        $this->assertFalse($service->isAllowedToSeeAllJobRuns());

        $this->expectException(PermissionException::class);
        $service->allowedToSeeJobRuns();
    }

    public function testAnonymousSessionIsDenied(): void
    {
        $service = new PermissionService();

        $this->assertFalse($service->isAllowedToSeeJobRuns());
        $this->assertFalse($service->isAllowedToSeeAllJobRuns());

        $this->expectException(PermissionException::class);
        $service->allowedToSeeJobRuns();
    }

    public function testAnonymousSessionIsDeniedSeeingAllJobRuns(): void
    {
        $this->expectException(PermissionException::class);
        (new PermissionService())->allowedToSeeAllJobRuns();
    }

    /**
     * The checks use the user of the request that runs them, not the one present when the
     * container happened to build the service.
     */
    public function testUserLoggedInAfterConstructionIsUsedForTheChecks(): void
    {
        $service = new PermissionService();
        $this->logIn($this->createUser([PermissionConstants::GEE_JOB_RUN]));

        $this->assertTrue($service->isAllowedToSeeJobRuns());
    }

    /**
     * @param string[] $permissions
     */
    private function createUser(array $permissions): User
    {
        $name = 'gee_permission_user_' . uniqid();
        $user = new User();
        $user->setParentId(0);
        $user->setName($name);
        $user->setPassword(Authentication::getPasswordHash($name, 'password'));
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions($permissions);
        $user->save();
        $this->createdUser = $user;

        return $user;
    }

    private function logIn(User $user): void
    {
        $this->sessionToken = serialize(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin'));
    }
}
