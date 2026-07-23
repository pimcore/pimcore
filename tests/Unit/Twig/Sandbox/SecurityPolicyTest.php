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

namespace Pimcore\Tests\Unit\Twig\Sandbox;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset;
use Pimcore\Model\User;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use stdClass;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

/**
 * @internal
 */
final class SecurityPolicyTest extends TestCase
{
    public function testBuiltInDenylistBlocksInfrastructureClassesByDefault(): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new PDO('sqlite::memory:'), 'query');
    }

    public function testDenylistModeAllowsArbitraryObjectsNotOnTheList(): void
    {
        $policy = new SecurityPolicy();

        // no exception expected
        $policy->checkMethodAllowed(new stdClass(), 'anything');
        $policy->checkPropertyAllowed(new stdClass(), 'anything');
        $this->addToAssertionCount(2);
    }

    public function testBlockedClassesExtendsTheBuiltInDenylist(): void
    {
        $policy = new SecurityPolicy(blockedClasses: [stdClass::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'anything');
    }

    public function testAllowlistModeDeniesEverythingNotOnTheAllowlist(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [PDO::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'anything');
    }

    public function testAllowlistModeAllowsListedClasses(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [stdClass::class]);

        $policy->checkMethodAllowed(new stdClass(), 'anything');
        $policy->checkPropertyAllowed(new stdClass(), 'anything');
        $this->addToAssertionCount(2);
    }

    public function testAllowlistModeDeactivatesTheDenylistEvenForBuiltInBlockedClasses(): void
    {
        // A non-empty allow list must take over completely: an infra class normally
        // blocked by the built-in denylist is allowed once it is itself allowlisted.
        $policy = new SecurityPolicy(allowedClasses: [PDO::class]);

        $policy->checkMethodAllowed(new PDO('sqlite::memory:'), 'query');
        $this->addToAssertionCount(1);
    }

    public function testAllowlistModeIsPropertyAware(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [stdClass::class]);

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed(new PDO('sqlite::memory:'), 'secret');
    }

    public function testSetAllowedClassesSwitchesModeAtRuntime(): void
    {
        $policy = new SecurityPolicy();

        // starts in denylist mode: unrelated object is reachable
        $policy->checkMethodAllowed(new stdClass(), 'anything');

        $policy->setAllowedClasses([PDO::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'anything');
    }

    public function testUserIsBlockedByDefault(): void
    {
        // GHSA-7gfm-v2fx-xrxm: User::getPassword()/getPasswordRecoveryToken() must not be
        // template-reachable. The whole class is blocked by default (defense in depth).
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new User(), 'getPassword');
    }

    public function testUserPropertyAccessIsBlockedByDefault(): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed(new User(), 'password');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function assetContentMethodsProvider(): iterable
    {
        yield 'getData' => ['getData'];
        yield 'getStream' => ['getStream'];
        yield 'getLocalFile' => ['getLocalFile'];
        yield 'getTemporaryFile' => ['getTemporaryFile'];
    }

    #[DataProvider('assetContentMethodsProvider')]
    public function testAssetContentMethodsAreAlwaysBlockedByDefault(string $method): void
    {
        // GHSA-7gfm-v2fx-xrxm: Asset stays reachable (needed for filename/thumbnail access
        // in templates), but its content-returning methods must never be callable.
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new Asset(), $method);
    }

    public function testAssetIsOtherwiseReachableByDefault(): void
    {
        $policy = new SecurityPolicy();

        // no exception expected: only the content-returning methods are blocked
        $policy->checkMethodAllowed(new Asset(), 'getId');
        $policy->checkMethodAllowed(new Asset(), 'getFilename');
        $this->addToAssertionCount(2);
    }

    public function testAlwaysBlockedMethodsSurviveAllowlistModeForUser(): void
    {
        // Even if a site explicitly allowlists User (e.g. to expose getFirstname()), the
        // secret-returning methods must remain unreachable.
        $policy = new SecurityPolicy(allowedClasses: [User::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new User(), 'getPasswordRecoveryToken');
    }

    public function testAlwaysBlockedMethodsSurviveAllowlistModeForAsset(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [Asset::class]);

        // getId remains reachable via the allowlist ...
        $policy->checkMethodAllowed(new Asset(), 'getId');
        $this->addToAssertionCount(1);

        // ... but getData is hard-blocked regardless.
        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new Asset(), 'getData');
    }
}
