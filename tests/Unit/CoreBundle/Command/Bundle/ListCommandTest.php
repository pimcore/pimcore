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
use Pimcore\Bundle\CoreBundle\Command\Bundle\ListCommand;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Bundle\UuidBundle\PimcoreUuidBundle;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class ListCommandTest extends TestCase
{
    private const ACTIVE_BUNDLE_CLASS = PimcoreSeoBundle::class;

    private const INACTIVE_BUNDLE_CLASS = PimcoreUuidBundle::class;

    private const DESCRIPTION = 'A very long description that definitely exceeds thirty characters';

    /**
     * Truncated to 30 characters including the ellipsis by
     * Symfony String's truncate().
     */
    private const TRUNCATED_DESCRIPTION = 'A very long description tha...';

    private function makeBundleManager(): PimcoreBundleManager
    {
        $activeBundle = $this->createStub(PimcoreBundleInterface::class);
        $activeBundle->method('getDescription')->willReturn(self::DESCRIPTION);
        $activeBundle->method('getVersion')->willReturn('1.2.3');

        $bundleManager = $this->createStub(PimcoreBundleManager::class);
        $bundleManager
            ->method('getAvailableBundles')
            ->willReturn([self::ACTIVE_BUNDLE_CLASS, self::INACTIVE_BUNDLE_CLASS]);
        $bundleManager
            ->method('getActiveBundle')
            ->willReturnCallback(static function (string $id) use ($activeBundle): PimcoreBundleInterface {
                if ($id !== self::ACTIVE_BUNDLE_CLASS) {
                    throw new BundleNotFoundException(sprintf('Bundle %s was not found', $id));
                }

                return $activeBundle;
            });
        $bundleManager->method('isInstalled')->willReturn(true);
        $bundleManager->method('canBeInstalled')->willReturn(false);
        $bundleManager->method('canBeUninstalled')->willReturn(true);
        $bundleManager->method('getManuallyRegisteredBundleState')->willReturn(['priority' => 10]);

        return $bundleManager;
    }

    private function runCommand(array $input): CommandTester
    {
        $tester = new CommandTester(new ListCommand($this->makeBundleManager()));
        $tester->execute($input, ['decorated' => false]);

        return $tester;
    }

    public function testTableOutputUsesNarrowHeadersAndLegend(): void
    {
        $tester = $this->runCommand([]);
        $display = $tester->getDisplay();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString(' I? ', $display);
        $this->assertStringContainsString(' UI? ', $display);
        $this->assertStringContainsString('Legend:', $display);
        $this->assertStringContainsString('I?: Can be installed?', $display);
        $this->assertStringContainsString('UI?: Can be uninstalled?', $display);
        $this->assertStringNotContainsString('Installable', $display);
    }

    public function testTableRowsRenderBundleStatesAsYesNo(): void
    {
        $display = $this->runCommand([])->getDisplay();

        $this->assertMatchesRegularExpression(
            '/PimcoreSeoBundle\s+\|\s+yes\s+\|\s+yes\s+\|\s+no\s+\|\s+yes\s+\|\s+10/',
            $display
        );
        $this->assertMatchesRegularExpression(
            '/PimcoreUuidBundle\s+\|\s+no\s+\|\s+no\s+\|\s+no\s+\|\s+no\s+\|\s+0/',
            $display
        );
    }

    public function testTableWithDetailsAddsTruncatedDescriptionAndVersion(): void
    {
        $tester = $this->runCommand(['--details' => true]);
        $display = $tester->getDisplay();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString(self::TRUNCATED_DESCRIPTION, $display);
        $this->assertStringContainsString('1.2.3', $display);
    }

    public function testJsonOutputKeepsFullKeyNamesAndRealBooleans(): void
    {
        $tester = $this->runCommand(['--json' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $json = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                'Bundle' => 'PimcoreSeoBundle',
                'Enabled' => true,
                'Installed' => true,
                'Installable' => false,
                'Uninstallable' => true,
                'Priority' => 10,
            ],
            $json[0]
        );
        $this->assertSame(
            [
                'Bundle' => 'PimcoreUuidBundle',
                'Enabled' => false,
                'Installed' => false,
                'Installable' => false,
                'Uninstallable' => false,
                'Priority' => 0,
            ],
            $json[1]
        );
    }

    public function testJsonWithDetailsKeepsRowsAlignedToHeaders(): void
    {
        $tester = $this->runCommand(['--json' => true, '--details' => true]);

        $json = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                'Bundle' => 'PimcoreSeoBundle',
                'Description' => self::TRUNCATED_DESCRIPTION,
                'Version' => '1.2.3',
                'Enabled' => true,
                'Installed' => true,
                'Installable' => false,
                'Uninstallable' => true,
                'Priority' => 10,
            ],
            $json[0]
        );
        $this->assertSame(
            [
                'Bundle' => 'PimcoreUuidBundle',
                'Description' => '',
                'Version' => '',
                'Enabled' => false,
                'Installed' => false,
                'Installable' => false,
                'Uninstallable' => false,
                'Priority' => 0,
            ],
            $json[1]
        );
    }

    public function testFullyQualifiedClassnamesOptionShowsFqcn(): void
    {
        $display = $this->runCommand(['--fully-qualified-classnames' => true])->getDisplay();

        $this->assertStringContainsString(self::ACTIVE_BUNDLE_CLASS, $display);
    }
}
