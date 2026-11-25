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
use Pimcore\Model\Document\Editable\Embed\UrlSanitizer;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Verifies that PimcoreCoreBundle::boot() installs the UrlSanitizer policy that the
 * "pimcore.documents.editables.embed_sanitizer.strict" config value (Configuration::createEmbedSanitizerNode())
 * resolves to, since that's the only place bridging the Symfony config value into the (non-DI-managed)
 * Embed editable's static sanitizer instance. Mirrors PimcoreCoreBundleLinkSanitizerTest for the
 * analogous Link editable wiring.
 */
class PimcoreCoreBundleEmbedSanitizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UrlSanitizer::setInstance(null);
    }

    protected function tearDown(): void
    {
        UrlSanitizer::setInstance(null);
        parent::tearDown();
    }

    public function testBootInstallsStrictPolicyWhenConfigured(): void
    {
        $this->bootWithParameter(true);

        $this->assertFalse(UrlSanitizer::getInstance()->isUrlAllowed('<b>not-a-url</b>'));
        $this->assertFalse(UrlSanitizer::getInstance()->isUrlAllowed('javascript:void(0)'));
    }

    public function testBootKeepsPermissivePolicyByDefault(): void
    {
        $this->bootWithParameter(false);

        $this->assertTrue(UrlSanitizer::getInstance()->isUrlAllowed('<b>not-a-url</b>'));
    }

    /**
     * Application bundles register at Symfony's default priority (0) and boot before
     * PimcoreCoreBundle (registered at -10 in Kernel::registerCoreBundlesToCollection()), since
     * BundleCollection::getItems() boots bundles in descending-priority order. So an application
     * bundle's boot() calling UrlSanitizer::setInstance() - the documented custom-policy hook -
     * already ran by the time PimcoreCoreBundle::boot() runs; it must not be overwritten, even
     * when the config value would otherwise disagree with it.
     */
    public function testBootDoesNotOverwriteAnAlreadyInstalledApplicationPolicy(): void
    {
        $applicationPolicy = UrlSanitizer::strict();
        UrlSanitizer::setInstance($applicationPolicy);

        // config says "strict: false", which would normally reset to permissive - but an
        // application bundle already installed its own policy first, so it must win
        $this->bootWithParameter(false);

        $this->assertSame($applicationPolicy, UrlSanitizer::getInstance());
    }

    /**
     * getInstance()'s lazy permissive fallback must not be mistaken by boot() for an
     * already-installed application policy - otherwise anything that merely reads the sanitizer
     * (e.g. rendering an Embed) before this bundle boots would silently suppress a configured
     * "strict: true" policy.
     */
    public function testReadingTheSanitizerBeforeBootDoesNotSuppressTheConfiguredStrictPolicy(): void
    {
        UrlSanitizer::getInstance();

        $this->bootWithParameter(true);

        $this->assertFalse(UrlSanitizer::getInstance()->isUrlAllowed('javascript:alert(1)'));
    }

    /**
     * Pimcore's own test suite boots multiple kernels/containers within one PHP process, so a
     * policy installed for one kernel must not leak into the next kernel's boot() and be mistaken
     * there for an application policy.
     */
    public function testShutdownResetsStateForTheNextKernelBoot(): void
    {
        $firstKernelBundle = $this->bootWithParameter(true);
        $this->assertFalse(UrlSanitizer::getInstance()->isUrlAllowed('javascript:alert(1)'));

        $firstKernelBundle->shutdown();

        $this->bootWithParameter(false);

        $this->assertTrue(UrlSanitizer::getInstance()->isUrlAllowed('javascript:alert(1)'));
    }

    private function bootWithParameter(bool $strict): PimcoreCoreBundle
    {
        return $this->bootWithParameters(['pimcore.documents.editables.embed_sanitizer.strict' => $strict]);
    }

    /**
     * Merges over the same defaults Configuration::createEmbedSanitizerNode() declares, since
     * PimcoreCoreExtension always sets all of them.
     */
    private function bootWithParameters(array $parameters): PimcoreCoreBundle
    {
        $bundle = new PimcoreCoreBundle();
        $bundle->setContainer(new Container(new ParameterBag($parameters + [
            'pimcore.documents.editables.embed_sanitizer.strict' => false,
            'pimcore.documents.editables.link_sanitizer.strict' => false,
            'pimcore.documents.editables.link_sanitizer.blocked_url_schemes' => ['javascript:', 'vbscript:'],
            'pimcore.documents.editables.link_sanitizer.block_unsafe_data_urls' => true,
            'pimcore.objects.link_sanitizer.strict' => false,
            'pimcore.objects.link_sanitizer.blocked_url_schemes' => ['javascript:', 'vbscript:'],
            'pimcore.objects.link_sanitizer.block_unsafe_data_urls' => true,
            'pimcore.objects.hydration_lock.disabled_for' => [],
        ])));
        $bundle->boot();

        return $bundle;
    }
}
