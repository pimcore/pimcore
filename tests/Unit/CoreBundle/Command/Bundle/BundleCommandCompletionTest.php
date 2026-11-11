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

namespace Pimcore\Tests\Unit\CoreBundle\Command\Bundle;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\CoreBundle\Command\Bundle\Helper\PostStateChange;
use Pimcore\Bundle\CoreBundle\Command\Bundle\InstallCommand;
use Pimcore\Bundle\CoreBundle\Command\Bundle\UninstallCommand;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandCompletionTester;

/**
 * @internal
 */
final class BundleCommandCompletionTest extends TestCase
{
    private function makeBundleManager(): PimcoreBundleManager
    {
        $bundleManager = $this->createStub(PimcoreBundleManager::class);
        $bundleManager
            ->method('getActiveBundles')
            ->willReturn([
                'Vendor\\Foo\\VendorFooBundle' => $this->makeBundle('VendorFooBundle'),
                'Vendor\\Bar\\VendorBarBundle' => $this->makeBundle('VendorBarBundle'),
            ]);

        return $bundleManager;
    }

    private function makeBundle(string $name): PimcoreBundleInterface
    {
        $bundle = $this->createStub(PimcoreBundleInterface::class);
        $bundle->method('getName')->willReturn($name);

        return $bundle;
    }

    private function makePostStateChangeHelper(): PostStateChange
    {
        return $this->createStub(PostStateChange::class);
    }

    /**
     * @var list<class-string<Command>>
     */
    private const BUNDLE_COMMAND_CLASSES = [
        InstallCommand::class,
        UninstallCommand::class,
    ];

    private function makeCompletionTester(string $commandClass): CommandCompletionTester
    {
        $command = new $commandClass($this->makeBundleManager(), $this->makePostStateChangeHelper());

        return new CommandCompletionTester($command);
    }

    public function testBundleArgumentSuggestsActiveBundleNames(): void
    {
        foreach (self::BUNDLE_COMMAND_CLASSES as $commandClass) {
            $this->assertSame(
                ['VendorFooBundle', 'VendorBarBundle'],
                $this->makeCompletionTester($commandClass)->complete(['']),
                sprintf('bundle argument suggestions of %s', $commandClass)
            );
        }
    }

    public function testNoBundleSuggestionsWhileTypingAnOption(): void
    {
        foreach (self::BUNDLE_COMMAND_CLASSES as $commandClass) {
            $this->assertNotContains(
                'VendorFooBundle',
                $this->makeCompletionTester($commandClass)->complete(['-']),
                sprintf('option completion of %s', $commandClass)
            );
        }
    }

    public function testNoBundleSuggestionsBeyondTheBundleArgument(): void
    {
        foreach (self::BUNDLE_COMMAND_CLASSES as $commandClass) {
            $this->assertNotContains(
                'VendorFooBundle',
                $this->makeCompletionTester($commandClass)->complete(['VendorFooBundle', '']),
                sprintf('completion beyond the bundle argument of %s', $commandClass)
            );
        }
    }
}
