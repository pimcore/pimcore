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
        $applicationPolicy = new AttributeSanitizer(blockDangerousUrlSchemes: true);
        AttributeSanitizer::setInstance($applicationPolicy);

        // config says "strict: false", which would normally reset to permissive - but an
        // application bundle already installed its own policy first, so it must win
        $this->bootWithParameter(false);

        $this->assertSame($applicationPolicy, AttributeSanitizer::getInstance());
    }

    private function bootWithParameter(bool $strict): void
    {
        $bundle = new PimcoreCoreBundle();
        $bundle->setContainer(new Container(new ParameterBag([
            'pimcore.documents.editables.link_sanitizer.strict' => $strict,
        ])));
        $bundle->boot();
    }
}
