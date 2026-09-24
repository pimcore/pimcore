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

namespace Pimcore\Tests\Unit\Bundle\CoreBundle;

use Pimcore\Bundle\CoreBundle\PimcoreCoreBundle;
use Pimcore\Model\Document\Editable\Link\AttributeSanitizer;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Verifies that PimcoreCoreBundle::boot() installs the AttributeSanitizer policy that the
 * "pimcore.documents.editables.link_sanitizer.strict" config value (Configuration::addDocumentsNode())
 * resolves to, since that's the only place bridging the Symfony config value into the (non-DI-managed)
 * Link editable's static sanitizer instance.
 */
class PimcoreCoreBundleLinkSanitizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // boot()'s behavior depends on AttributeSanitizer::isConfigured(), so every test needs a
        // known clean starting state rather than relying on the previous test's tearDown having
        // run - the full suite runs many test classes in one process, in an order these tests
        // don't control
        AttributeSanitizer::setInstance(null);
    }

    protected function tearDown(): void
    {
        AttributeSanitizer::setInstance(null);
        parent::tearDown();
    }

    public function testBootInstallsStrictPolicyWhenConfigured(): void
    {
        $this->bootWithParameter(true);

        $this->assertFalse(AttributeSanitizer::getInstance()->isUrlAllowed('javascript:alert(document.domain)'));
        $this->assertFalse(AttributeSanitizer::getInstance()->isAttributeKeyAllowed('onclick', true));
    }

    public function testBootKeepsPermissivePolicyByDefault(): void
    {
        $this->bootWithParameter(false);

        $this->assertTrue(AttributeSanitizer::getInstance()->isUrlAllowed('javascript:alert(document.domain)'));
        $this->assertTrue(AttributeSanitizer::getInstance()->isAttributeKeyAllowed('onclick', true));
    }

    public function testBootIsPermissiveWhenParameterIsMissing(): void
    {
        $bundle = new PimcoreCoreBundle();
        $bundle->setContainer(new Container(new ParameterBag()));
        $bundle->boot();

        $this->assertTrue(AttributeSanitizer::getInstance()->isUrlAllowed('javascript:alert(document.domain)'));
    }

    /**
     * Application bundles register at Symfony's default priority (0) and boot before
     * PimcoreCoreBundle (registered at -10 in Kernel::registerCoreBundlesToCollection()), since
     * BundleCollection::getItems() boots bundles in descending-priority order. So an application
     * bundle's boot() calling AttributeSanitizer::setInstance() - the documented custom-policy
     * hook - already ran by the time PimcoreCoreBundle::boot() runs; it must not be overwritten,
     * even when the config value would otherwise disagree with it.
     */
    public function testBootDoesNotOverwriteAnAlreadyInstalledApplicationPolicy(): void
    {
        $applicationPolicy = new AttributeSanitizer(blockedUrlSchemes: ['javascript:']);
        AttributeSanitizer::setInstance($applicationPolicy);

        // config says "strict: false", which would normally reset to permissive - but an
        // application bundle already installed its own policy first, so it must win
        $this->bootWithParameter(false);

        $this->assertSame($applicationPolicy, AttributeSanitizer::getInstance());
    }

    public function testBootUsesTheConfiguredBlockedUrlSchemesList(): void
    {
        $this->bootWithParameters([
            'pimcore.documents.editables.link_sanitizer.strict' => true,
            'pimcore.documents.editables.link_sanitizer.blocked_url_schemes' => ['mailto:'],
            'pimcore.documents.editables.link_sanitizer.block_unsafe_data_urls' => false,
        ]);

        $sanitizer = AttributeSanitizer::getInstance();
        $this->assertFalse($sanitizer->isUrlAllowed('mailto:someone@example.com'));
        // not in the configured list, so it's allowed even under a strict: true config
        $this->assertTrue($sanitizer->isUrlAllowed('javascript:alert(1)'));
        // block_unsafe_data_urls is configured off
        $this->assertTrue($sanitizer->isUrlAllowed('data:text/html,<script>alert(1)</script>'));
    }

    public function testBootFallsBackToDefaultBlockedUrlSchemesWhenParameterIsMissing(): void
    {
        $this->bootWithParameters([
            'pimcore.documents.editables.link_sanitizer.strict' => true,
        ]);

        $sanitizer = AttributeSanitizer::getInstance();
        $this->assertFalse($sanitizer->isUrlAllowed('javascript:alert(1)'));
        $this->assertFalse($sanitizer->isUrlAllowed('vbscript:msgbox("x")'));
    }

    /**
     * getInstance()'s lazy permissive fallback must not be mistaken by boot() for an
     * already-installed application policy - otherwise anything that merely reads the sanitizer
     * (e.g. rendering a Link) before this bundle boots would silently suppress a configured
     * "strict: true" policy. See AttributeSanitizer::$explicitlyConfigured.
     */
    public function testReadingTheSanitizerBeforeBootDoesNotSuppressTheConfiguredStrictPolicy(): void
    {
        AttributeSanitizer::getInstance();

        $this->bootWithParameter(true);

        $this->assertFalse(AttributeSanitizer::getInstance()->isUrlAllowed('javascript:alert(document.domain)'));
    }

    /**
     * Pimcore's own test suite boots multiple kernels/containers within one PHP process (see
     * lib/Kernel.php's shutdown-function comment), so a policy installed for one kernel must not
     * leak into the next kernel's boot() and be mistaken there for an application policy.
     */
    public function testShutdownResetsStateForTheNextKernelBoot(): void
    {
        $firstKernelBundle = new PimcoreCoreBundle();
        $firstKernelBundle->setContainer(new Container(new ParameterBag([
            'pimcore.documents.editables.link_sanitizer.strict' => true,
        ])));
        $firstKernelBundle->boot();
        $this->assertFalse(AttributeSanitizer::getInstance()->isUrlAllowed('javascript:alert(1)'));

        $firstKernelBundle->shutdown();

        $secondKernelBundle = new PimcoreCoreBundle();
        $secondKernelBundle->setContainer(new Container(new ParameterBag([
            'pimcore.documents.editables.link_sanitizer.strict' => false,
        ])));
        $secondKernelBundle->boot();

        $this->assertTrue(AttributeSanitizer::getInstance()->isUrlAllowed('javascript:alert(1)'));
    }

    private function bootWithParameter(bool $strict): void
    {
        $this->bootWithParameters(['pimcore.documents.editables.link_sanitizer.strict' => $strict]);
    }

    private function bootWithParameters(array $parameters): void
    {
        $bundle = new PimcoreCoreBundle();
        $bundle->setContainer(new Container(new ParameterBag($parameters)));
        $bundle->boot();
    }
}
