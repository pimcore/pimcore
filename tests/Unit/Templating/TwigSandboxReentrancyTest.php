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
    private function createEngine(?SecurityPolicy $policy = null): TwigDefaultDelegatingEngine
    {
        $twig = new Environment(new ArrayLoader([]));
        $twig->addExtension(new SandboxExtension($policy ?? new SecurityPolicy(), false));

        return new TwigDefaultDelegatingEngine($twig, new Config());
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

    public function testOuterTemplateStaysRestrictedAfterNestedSandboxedRender(): void
    {
        // 'escape' is allowed as in the default policy (default.yaml): autoescaping applies it to
        // {{ }} output, and older Twig versions check that filter against the sandbox policy
        $policy = new SecurityPolicy(
            allowedTags: ['set'],
            allowedFilters: ['escape'],
            allowedClasses: [SandboxNestingProbe::class],
        );
        $engine = $this->createEngine($policy);
        $probe = new SandboxNestingProbe($engine);

        $twig = $engine->getTwigEnvironment(true);

        $caught = null;

        try {
            $template = $twig->createTemplate('{% set inner = probe.renderNested() %}{{ target.reveal() }}');
            $template->render(['probe' => $probe, 'target' => new SandboxProbeTarget()]);
        } catch (SecurityNotAllowedMethodError $e) {
            $caught = $e;
        } finally {
            $engine->disableSandboxExtensionFromTwigEnvironment();
        }

        $this->assertInstanceOf(SecurityNotAllowedMethodError::class, $caught, 'Outer template must stay sandboxed');
        $this->assertFalse($this->isSandboxed($engine), 'Sandbox must be disabled after the outermost render');
    }

    public function testLayoutMutatorsAreBlockedByDefaultPatterns(): void
    {
        $policy = new SecurityPolicy();

        $cases = [[new Text(), 'setHtml'], [new Text(), 'enrichLayoutDefinition'], [new Panel(), 'setTitle']];
        foreach ($cases as [$obj, $method]) {
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

/**
 * @internal
 */
final class SandboxNestingProbe
{
    public function __construct(private readonly TwigDefaultDelegatingEngine $engine)
    {
    }

    public function renderNested(): string
    {
        try {
            return $this->engine->getTwigEnvironment(true)->createTemplate('inner')->render([]);
        } finally {
            $this->engine->disableSandboxExtensionFromTwigEnvironment();
        }
    }
}

/**
 * @internal
 */
final class SandboxProbeTarget
{
    public function reveal(): string
    {
        return 'unrestricted';
    }
}
