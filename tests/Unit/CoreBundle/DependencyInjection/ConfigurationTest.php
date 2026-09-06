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

use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    private function processSkipInitialVersion(mixed $value): mixed
    {
        $config = $this->process([[
            'assets' => [
                'versions' => [
                    'skip_initial_version' => $value,
                ],
            ],
        ]]);

        return $config['assets']['versions']['skip_initial_version'];
    }

    public function testSkipInitialVersionDefaultsToFalse(): void
    {
        $config = $this->process([[]]);

        $this->assertFalse($config['assets']['versions']['skip_initial_version']);
    }

    public function testSkipInitialVersionAcceptsBooleans(): void
    {
        $this->assertTrue($this->processSkipInitialVersion(true));
        $this->assertFalse($this->processSkipInitialVersion(false));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function booleanStringProvider(): array
    {
        return [
            'true' => ['true', true],
            'yes' => ['yes', true],
            'on' => ['on', true],
            '1' => ['1', true],
            'false' => ['false', false],
            'no' => ['no', false],
            'off' => ['off', false],
            '0' => ['0', false],
            'empty' => ['', false],
        ];
    }

    /**
     * @dataProvider booleanStringProvider
     */
    public function testSkipInitialVersionNormalizesBooleanStrings(string $value, bool $expected): void
    {
        $this->assertSame($expected, $this->processSkipInitialVersion($value));
    }

    public function testSkipInitialVersionRejectsUnrecognizedStrings(): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->processSkipInitialVersion('maybe');
    }
}
