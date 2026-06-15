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

namespace Pimcore\Tests\Unit\Tool;

use Pimcore\Model\User as PimcoreUser;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Authentication;
use ReflectionMethod;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Regression tests for GHSA-46p3-pxr2-m4j9 — Authentication::safelyUnserialize() must not
 * instantiate arbitrary classes when deserializing the pimcore_admin session token.
 */
class AuthenticationTest extends TestCase
{
    private function safelyUnserialize(string $payload): mixed
    {
        $method = new ReflectionMethod(Authentication::class, 'safelyUnserialize');
        $method->setAccessible(true);

        return $method->invoke(null, $payload);
    }

    /**
     * Security regression: a serialized object-injection gadget must NOT have its magic
     * methods (__wakeup/__destruct) triggered during deserialization, and must not be
     * returned as a live instance.
     */
    public function testGadgetClassIsNotInstantiated(): void
    {
        // Building the payload constructs and destructs a temporary, which flips the flag;
        // reset it AFTER the payload exists so we only observe deserialization side effects.
        $payload = serialize(new AuthDeserializeCanary());
        AuthDeserializeCanary::$fired = false;

        $result = $this->safelyUnserialize($payload);

        $this->assertFalse(
            AuthDeserializeCanary::$fired,
            'A non-allowlisted class must not have its magic methods executed during unserialize().'
        );
        $this->assertNotInstanceOf(AuthDeserializeCanary::class, $result);
        $this->assertNull($result, 'A foreign/tampered payload must be rejected (null).');
    }

    /**
     * A gadget nested inside an otherwise-allowed token graph must also be neutralised
     * (reduced to __PHP_Incomplete_Class) without firing its magic methods.
     */
    public function testNestedGadgetInsideAllowedTokenIsNeutralised(): void
    {
        $token = new UsernamePasswordToken($this->createSecurityUser('admin'), 'pimcore_admin');
        // Token attributes are part of the serialized graph; roles must stay strings, so the
        // gadget is nested via an attribute rather than the roles constructor argument.
        $token->setAttribute('gadget', new AuthDeserializeCanary());

        $payload = serialize($token);
        AuthDeserializeCanary::$fired = false;

        $result = $this->safelyUnserialize($payload);

        $this->assertFalse(AuthDeserializeCanary::$fired, 'Nested gadget magic methods must not run.');
        $this->assertInstanceOf(TokenInterface::class, $result);
    }

    /**
     * Functional regression: a legitimate session token (the common case) must still
     * deserialize into a real TokenInterface so admins are not logged out.
     */
    public function testAllowedSecurityTokenSurvivesDeserialization(): void
    {
        $token = new UsernamePasswordToken($this->createSecurityUser('admin'), 'pimcore_admin');
        $payload = serialize($token);

        $result = $this->safelyUnserialize($payload);

        $this->assertInstanceOf(TokenInterface::class, $result);
        $this->assertNotInstanceOf(\__PHP_Incomplete_Class::class, $result->getUser());
        $this->assertInstanceOf(SecurityUser::class, $result->getUser());
        $this->assertSame('admin', $result->getUser()->getUserIdentifier());
    }

    public function testAllowedClassListContainsExpectedTokenAndUserClasses(): void
    {
        $allowed = Authentication::getSessionTokenAllowedClasses();

        $this->assertContains(SecurityUser::class, $allowed);
        $this->assertContains(PimcoreUser::class, $allowed);
        $this->assertContains(UsernamePasswordToken::class, $allowed);
        $this->assertContains(
            \Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken::class,
            $allowed
        );
        // The classic admin-ui-classic-bundle login (Pimcore < 2026.1 / LTS) stores this subclass
        // while 2FA is pending; it must be allowlisted so those sessions are not invalidated.
        $this->assertContains(
            'Pimcore\\Bundle\\AdminBundle\\Security\\Authentication\\Token\\TwoFactorRequiredToken',
            $allowed
        );
    }

    private function createSecurityUser(string $name): SecurityUser
    {
        $pimcoreUser = new PimcoreUser();
        $pimcoreUser->setId(1);
        $pimcoreUser->setName($name);

        return new SecurityUser($pimcoreUser);
    }
}

/**
 * Canary "gadget": records whether its magic methods were ever executed.
 */
class AuthDeserializeCanary
{
    public static bool $fired = false;

    public function __wakeup(): void
    {
        self::$fired = true;
    }

    public function __destruct()
    {
        self::$fired = true;
    }
}
