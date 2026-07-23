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
}
