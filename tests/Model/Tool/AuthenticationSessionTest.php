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

namespace Pimcore\Tests\Model\Tool;

use Pimcore\Bundle\AdminBundle\Security\Authentication\Token\TwoFactorRequiredToken;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Authentication;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * A session whose login is still waiting for the second factor must not be accepted as a
 * completed login by Authentication::authenticateSession(), nor by the request helper checks
 * built on top of it, while sessions of completed logins keep working.
 */
class AuthenticationSessionTest extends ModelTestCase
{
    private ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        // a stand-in for the classic admin UI token, which is not a core dependency
        if (!class_exists(TwoFactorRequiredToken::class, false)) {
            require_once __DIR__ . '/../../Support/Fixtures/AdminUiClassic/TwoFactorRequiredToken.php';
        }
    }

    protected function tearDown(): void
    {
        $this->user?->delete();
        $this->user = null;

        parent::tearDown();
    }

    public function testSchebPendingTwoFactorSessionIsNotAuthenticated(): void
    {
        $token = new TwoFactorToken(
            new PostAuthenticationToken($this->createSecurityUser(), 'pimcore_admin', []),
            null,
            'pimcore_admin',
            ['google']
        );
        $request = $this->createRequest($token);

        $this->assertNull(Authentication::authenticateSession($request));
        $this->assertFalse($this->createRequestHelper($request)->isAuthenticatedFrontendRequestByAdmin($request));
    }

    public function testClassicAdminTwoFactorRequiredSessionIsNotAuthenticated(): void
    {
        $token = new TwoFactorRequiredToken($this->createSecurityUser(), 'pimcore_admin', []);
        $request = $this->createRequest($token);

        $this->assertNull(Authentication::authenticateSession($request));
        $this->assertFalse($this->createRequestHelper($request)->isAuthenticatedFrontendRequestByAdmin($request));
    }

    public function testCompletedLoginSessionsAreAuthenticated(): void
    {
        $tokens = [
            'UsernamePasswordToken' => new UsernamePasswordToken($this->createSecurityUser(), 'pimcore_admin'),
            'PostAuthenticationToken' => new PostAuthenticationToken($this->createSecurityUser(), 'pimcore_admin', []),
        ];

        foreach ($tokens as $label => $token) {
            $request = $this->createRequest($token);

            $authenticatedUser = Authentication::authenticateSession($request);

            $this->assertInstanceOf(User::class, $authenticatedUser, $label);
            $this->assertSame($this->user?->getId(), $authenticatedUser->getId(), $label);
            $this->assertTrue($this->createRequestHelper($request)->isAuthenticatedFrontendRequestByAdmin($request), $label);
        }
    }

    private function createRequest(TokenInterface $token): Request
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $session->set('_security_pimcore_admin', serialize($token));

        $request = Request::create('http://localhost/some/page', 'GET', ['pimcore_preview' => '1']);
        $request->cookies->set($session->getName(), $session->getId());
        $request->setSession($session);

        return $request;
    }

    private function createRequestHelper(Request $request): RequestHelper
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new RequestHelper($requestStack, new RequestContext());
    }

    private function createSecurityUser(): SecurityUser
    {
        if (null === $this->user) {
            $name = 'pending_two_factor_admin_' . uniqid();

            $user = new User();
            $user->setParentId(0);
            $user->setName($name);
            $user->setPassword(Authentication::getPasswordHash($name, 'password'));
            $user->setActive(true);
            $user->setAdmin(true);
            $user->save();

            $this->user = $user;
        }

        return new SecurityUser($this->user);
    }
}
