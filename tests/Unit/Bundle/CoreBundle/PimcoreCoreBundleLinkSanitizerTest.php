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

    private function bootWithParameter(bool $strict): void
    {
        $bundle = new PimcoreCoreBundle();
        $bundle->setContainer(new Container(new ParameterBag([
            'pimcore.documents.editables.link_sanitizer.strict' => $strict,
        ])));
        $bundle->boot();
    }
}
