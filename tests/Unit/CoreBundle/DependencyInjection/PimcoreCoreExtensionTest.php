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

namespace Pimcore\Tests\Unit\CoreBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension;
use Pimcore\Tests\Support\Test\TestCase;
use Scheb\TwoFactorBundle\DependencyInjection\Configuration as TwoFactorConfiguration;
use Scheb\TwoFactorBundle\DependencyInjection\SchebTwoFactorExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The Google Authenticator server name has to be prepended from `pimcore.general.domain`: a
 * `%router.request_context.host%` default in the scheb_two_factor config is resolved before
 * PimcoreCoreExtension sets that parameter from the domain, so it always ended up as "localhost".
 */
final class PimcoreCoreExtensionTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $pimcoreConfigs
     * @param array<int, array<string, mixed>> $twoFactorConfigs
     */
    private function prepend(array $pimcoreConfigs, array $twoFactorConfigs = []): ContainerBuilder
    {
        $extension = new PimcoreCoreExtension();
        $container = new ContainerBuilder();
        $container->registerExtension($extension);
        $container->registerExtension(new SchebTwoFactorExtension());
        foreach ($pimcoreConfigs as $config) {
            $container->loadFromExtension('pimcore', $config);
        }
        foreach ($twoFactorConfigs as $config) {
            $container->loadFromExtension('scheb_two_factor', $config);
        }

        $extension->prepend($container);

        return $container;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveGoogleConfig(ContainerBuilder $container): array
    {
        $config = (new Processor())->processConfiguration(
            new TwoFactorConfiguration(),
            $container->getExtensionConfig('scheb_two_factor')
        );

        return $config['google'];
    }

    private function resolveGoogleServerName(ContainerBuilder $container): ?string
    {
        return $this->resolveGoogleConfig($container)['server_name'];
    }

    public function testConfiguredDomainBecomesTheGoogleServerName(): void
    {
        $container = $this->prepend([['general' => ['domain' => 'www.example.com']]]);

        $this->assertSame('www.example.com', $this->resolveGoogleServerName($container));
    }

    public function testLastConfiguredDomainWins(): void
    {
        $container = $this->prepend([
            ['general' => ['domain' => 'www.example.com']],
            ['general' => ['domain' => 'shop.example.com']],
        ]);

        $this->assertSame('shop.example.com', $this->resolveGoogleServerName($container));
    }

    public function testExplicitGoogleServerNameTakesPrecedence(): void
    {
        $container = $this->prepend(
            [['general' => ['domain' => 'www.example.com']]],
            [['google' => ['server_name' => 'Custom Server']]]
        );

        $this->assertSame('Custom Server', $this->resolveGoogleServerName($container));
    }

    /**
     * Bundle configuration such as admin-ui-classic-bundle's `two_factor_authentication.yaml` is loaded as
     * regular (not prepended) config, so a `server_name` set there would win over the domain - which is why
     * that bundle no longer ships one. Its remaining google settings must not affect the domain default.
     */
    public function testBundleConfigWithoutServerNameKeepsTheDomain(): void
    {
        $container = $this->prepend(
            [['general' => ['domain' => 'www.example.com']]],
            [['google' => ['enabled' => true, 'issuer' => 'Pimcore']]]
        );

        $google = $this->resolveGoogleConfig($container);

        $this->assertSame('www.example.com', $google['server_name']);
        $this->assertSame('Pimcore', $google['issuer']);
        $this->assertTrue($google['enabled']);
    }

    public function testNothingIsPrependedWithoutADomain(): void
    {
        $container = $this->prepend([['general' => ['domain' => '']], []]);

        $this->assertSame([], $container->getExtensionConfig('scheb_two_factor'));
    }

    public function testLaterNullDomainClearsTheDomain(): void
    {
        $container = $this->prepend([
            ['general' => ['domain' => 'www.example.com']],
            ['general' => ['domain' => null]],
        ]);

        $this->assertSame([], $container->getExtensionConfig('scheb_two_factor'));
    }
}
