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

namespace Pimcore\Tests\Unit\Templating;

use PHPUnit\Framework\TestCase;
use Pimcore\Config;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Text;
use Pimcore\Templating\TwigDefaultDelegatingEngine;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityNotAllowedMethodError;

/**
 * @internal
 */
final class TwigSandboxReentrancyTest extends TestCase
{
    private function createEngine(): TwigDefaultDelegatingEngine
    {
        $twig = new Environment(new ArrayLoader([]));
        $twig->addExtension(new SandboxExtension(new SecurityPolicy(), false));

        return new TwigDefaultDelegatingEngine($twig, $this->createStub(Config::class));
    }

    private function isSandboxed(TwigDefaultDelegatingEngine $engine): bool
    {
        return $engine->getTwigEnvironment()->getExtension(SandboxExtension::class)->isSandboxed();
    }

    public function testNestedSandboxedRenderKeepsOuterSandboxEnabled(): void
    {
        $engine = $this->createEngine();

        $engine->getTwigEnvironment(true);
        $engine->getTwigEnvironment(true);
        $engine->disableSandboxExtensionFromTwigEnvironment();

        $this->assertTrue($this->isSandboxed($engine));
    }

    public function testSandboxIsDisabledAfterOutermostRender(): void
    {
        $engine = $this->createEngine();

        $engine->getTwigEnvironment(true);
        $engine->getTwigEnvironment(true);
        $engine->disableSandboxExtensionFromTwigEnvironment();
        $engine->disableSandboxExtensionFromTwigEnvironment();

        $this->assertFalse($this->isSandboxed($engine));
    }

    public function testSingleRenderStillDisablesSandbox(): void
    {
        $engine = $this->createEngine();

        $engine->getTwigEnvironment(true);
        $this->assertTrue($this->isSandboxed($engine));
        $engine->disableSandboxExtensionFromTwigEnvironment();

        $this->assertFalse($this->isSandboxed($engine));
    }

    public function testLayoutMutatorsAreBlockedByDefaultPatterns(): void
    {
        $policy = new SecurityPolicy();

        foreach ([[new Text(), 'setHtml'], [new Text(), 'enrichLayoutDefinition'], [new Panel(), 'setTitle']] as [$obj, $method]) {
            try {
                $policy->checkMethodAllowed($obj, $method);
                $this->fail($method . ' must be blocked');
            } catch (SecurityNotAllowedMethodError) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testLayoutGettersStayAllowed(): void
    {
        $policy = new SecurityPolicy();

        $policy->checkMethodAllowed(new Text(), 'getName');
        $policy->checkMethodAllowed(new Text(), 'getHtml');
        $this->addToAssertionCount(1);
    }
}
