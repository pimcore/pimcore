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

namespace Pimcore\Tests\Unit\Controller\Config;

use Pimcore\Controller\Config\Bundle\BundleProvider;
use Pimcore\Controller\Config\ControllerDataProvider;
use Pimcore\Controller\Config\Template\TemplateProviderInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ControllerDataProviderTest extends TestCase
{
    private function createBundleProvider(): BundleProvider
    {
        $kernel = $this->createMock(KernelInterface::class);
        // no project bundles -> getTemplates() only relies on the registered template providers
        $kernel->method('getBundles')->willReturn([]);

        return new BundleProvider($kernel);
    }

    private function createTemplateProvider(array $templates): TemplateProviderInterface
    {
        return new class($templates) implements TemplateProviderInterface {
            public function __construct(private array $templates)
            {
            }

            public function getTemplates(): array
            {
                return $this->templates;
            }
        };
    }

    public function testGetTemplatesIncludesTemplateProviderTemplates(): void
    {
        $provider = new ControllerDataProvider(
            $this->createBundleProvider(),
            [],
            [$this->createTemplateProvider(['@Agent/generated/foo.html.twig'])]
        );

        $templates = $provider->getTemplates();

        $this->assertContains('@Agent/generated/foo.html.twig', $templates);
    }

    public function testGetTemplatesMergesMultipleTemplateProviders(): void
    {
        $provider = new ControllerDataProvider(
            $this->createBundleProvider(),
            [],
            [
                $this->createTemplateProvider(['content/one.html.twig']),
                $this->createTemplateProvider(['@Agent/two.html.twig']),
            ]
        );

        $templates = $provider->getTemplates();

        $this->assertContains('content/one.html.twig', $templates);
        $this->assertContains('@Agent/two.html.twig', $templates);
    }

    public function testGetTemplatesDeduplicatesProviderTemplates(): void
    {
        $duplicate = 'content/duplicate.html.twig';

        $provider = new ControllerDataProvider(
            $this->createBundleProvider(),
            [],
            [
                $this->createTemplateProvider([$duplicate, '@Agent/unique.html.twig']),
                $this->createTemplateProvider([$duplicate]),
            ]
        );

        $templates = $provider->getTemplates();

        $this->assertSame(
            [$duplicate],
            array_values(array_filter($templates, static fn (string $t): bool => $t === $duplicate)),
            'Duplicate template names contributed by providers must be collapsed to a single entry.'
        );
        $this->assertContains('@Agent/unique.html.twig', $templates);
        // returned list must be a plain, re-indexed array
        $this->assertSame(array_values($templates), $templates);
    }

    public function testGetTemplatesIsMemoised(): void
    {
        $templateProvider = $this->createMock(TemplateProviderInterface::class);
        $templateProvider
            ->expects($this->once())
            ->method('getTemplates')
            ->willReturn(['@Agent/memoised.html.twig']);

        $provider = new ControllerDataProvider(
            $this->createBundleProvider(),
            [],
            [$templateProvider]
        );

        $firstCallTemplates = $provider->getTemplates();
        $this->assertContains('@Agent/memoised.html.twig', $firstCallTemplates);

        $secondCallTemplates = $provider->getTemplates();
        $this->assertSame($firstCallTemplates, $secondCallTemplates);
    }
}
