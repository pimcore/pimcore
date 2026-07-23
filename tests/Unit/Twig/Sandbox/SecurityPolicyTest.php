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
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset;
use Pimcore\Model\User;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use stdClass;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
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
        $policy->checkMethodAllowed($this->createStub(PDO::class), 'query');
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

        $policy->checkMethodAllowed($this->createStub(PDO::class), 'query');
        $this->addToAssertionCount(1);
    }

    public function testAllowlistModeIsPropertyAware(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [stdClass::class]);

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed($this->createStub(PDO::class), 'secret');
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function userSecretMethodsProvider(): iterable
    {
        yield 'getPassword' => ['getPassword'];
        yield 'getPasswordRecoveryToken' => ['getPasswordRecoveryToken'];
        yield 'getTwoFactorAuthentication' => ['getTwoFactorAuthentication'];
    }

    /**
     * @dataProvider userSecretMethodsProvider
     */
    public function testUserIsBlockedByDefault(string $method): void
    {
        // GHSA-7gfm-v2fx-xrxm: User::getPassword()/getPasswordRecoveryToken() and
        // getTwoFactorAuthentication() (returns the MFA secret, models/User.php:679) must
        // not be template-reachable. The whole class is blocked by default (defense in depth).
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new User(), $method);
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

    /**
     * @dataProvider assetContentMethodsProvider
     */
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

    /**
     * @dataProvider userSecretMethodsProvider
     */
    public function testAlwaysBlockedMethodsSurviveAllowlistModeForUser(string $method): void
    {
        // Even if a site explicitly allowlists User (e.g. to expose getFirstname()), the
        // secret-returning methods - including getTwoFactorAuthentication(), which returns
        // the MFA secret - must remain unreachable.
        $policy = new SecurityPolicy(allowedClasses: [User::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new User(), $method);
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function idLookupPimcoreFunctionsProvider(): iterable
    {
        yield 'pimcore_asset' => ['pimcore_asset'];
        yield 'pimcore_asset_by_path' => ['pimcore_asset_by_path'];
        yield 'pimcore_document' => ['pimcore_document'];
        yield 'pimcore_document_by_path' => ['pimcore_document_by_path'];
        yield 'pimcore_document_wrap_hardlink' => ['pimcore_document_wrap_hardlink'];
        yield 'pimcore_object' => ['pimcore_object'];
        yield 'pimcore_object_by_path' => ['pimcore_object_by_path'];
        yield 'pimcore_object_classificationstore_group' => ['pimcore_object_classificationstore_group'];
        yield 'pimcore_object_brick_definition_key' => ['pimcore_object_brick_definition_key'];
        yield 'pimcore_site' => ['pimcore_site'];
        yield 'pimcore_site_by_root_id' => ['pimcore_site_by_root_id'];
        yield 'pimcore_site_by_domain' => ['pimcore_site_by_domain'];
        yield 'pimcore_site_current' => ['pimcore_site_current'];
        yield 'pimcore_user' => ['pimcore_user'];
    }

    /**
     * @dataProvider idLookupPimcoreFunctionsProvider
     */
    public function testIdLookupPimcoreFunctionsAreNotAutoAllowedByDefault(string $function): void
    {
        // GHSA-7gfm-v2fx-xrxm remediation #3: the pimcore_* auto-allow must not cover
        // functions that hand back a live model instance looked up by id/path.
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], [$function]);
    }

    public function testIdLookupPimcoreFunctionCanBeExplicitlyAllowed(): void
    {
        $policy = new SecurityPolicy(allowedFunctions: ['pimcore_user']);

        // no exception expected
        $policy->checkSecurity([], [], ['pimcore_user']);
        $this->addToAssertionCount(1);
    }

    public function testOtherPimcoreFunctionsRemainAutoAllowedByDefault(): void
    {
        $policy = new SecurityPolicy();

        // a rendering/helper pimcore_* function not on the explicit-allow list
        $policy->checkSecurity([], [], ['pimcore_dump']);
        $this->addToAssertionCount(1);
    }

    public function testNonPimcoreFunctionsStillRequireExplicitAllow(): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['file_get_contents']);
    }

    public function testBlockedFunctionsExtendsTheBuiltInDenylist(): void
    {
        $policy = new SecurityPolicy(blockedFunctions: ['pimcore_dump']);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['pimcore_dump']);
    }

    public function testAllowedFunctionsSwitchesToAllowlistModeAndDeactivatesDenylist(): void
    {
        // A non-empty allow list must take over completely: a pimcore_* function normally
        // blocked by the built-in denylist is allowed once it is itself allowlisted.
        $policy = new SecurityPolicy(allowedPimcoreFunctions: ['pimcore_user']);

        $policy->checkSecurity([], [], ['pimcore_user']);
        $this->addToAssertionCount(1);
    }

    public function testAllowlistModeDeniesPimcoreFunctionsNotOnTheAllowlist(): void
    {
        // In allowlist mode the prefix auto-allow is fully deactivated: even a function
        // that was previously auto-allowed (not on the built-in denylist) is now denied
        // unless explicitly listed.
        $policy = new SecurityPolicy(allowedPimcoreFunctions: ['pimcore_user']);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['pimcore_dump']);
    }

    public function testAllowedFunctionsIsIndependentOfTheGeneralFunctionsAllowlist(): void
    {
        // $allowedFunctions (the pre-existing `functions` config) keeps working
        // unconditionally, in either mode.
        $policy = new SecurityPolicy(allowedFunctions: ['path'], allowedPimcoreFunctions: ['pimcore_user']);

        $policy->checkSecurity([], [], ['path']);
        $this->addToAssertionCount(1);
    }

    public function testSetBlockedFunctionsAndSetAllowedPimcoreFunctionsSwitchModeAtRuntime(): void
    {
        $policy = new SecurityPolicy();

        // starts in denylist mode: a non-denylisted pimcore_* function is auto-allowed
        $policy->checkSecurity([], [], ['pimcore_dump']);

        $policy->setAllowedPimcoreFunctions(['pimcore_user']);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['pimcore_dump']);
    }
}
