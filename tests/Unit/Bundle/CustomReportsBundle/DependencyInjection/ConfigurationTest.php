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

namespace Pimcore\Tests\Unit\Bundle\CustomReportsBundle\DependencyInjection;

use Pimcore\Bundle\CustomReportsBundle\DependencyInjection\Configuration;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * Covers the sql_adapter.denied_tables/denied_columns config node: since configurability is the
 * public escape hatch for legitimate reports (adjusting or disabling the deny-list), a wiring/merge
 * regression here could silently weaken or over-restrict the security control in Sql.php.
 */
class ConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    public function testDefaultDenyListValues(): void
    {
        $config = $this->process([]);

        $this->assertSame(['users'], $config['sql_adapter']['denied_tables']);
        $this->assertSame(
            ['password', 'passwordRecoveryToken', 'twoFactorAuthentication', 'apiKey', 'secret', 'token'],
            $config['sql_adapter']['denied_columns']
        );
    }

    public function testCustomDenyListValuesOverrideDefaults(): void
    {
        $config = $this->process([
            [
                'sql_adapter' => [
                    'denied_tables' => ['custom_table'],
                    'denied_columns' => ['custom_column'],
                ],
            ],
        ]);

        $this->assertSame(['custom_table'], $config['sql_adapter']['denied_tables']);
        $this->assertSame(['custom_column'], $config['sql_adapter']['denied_columns']);
    }

    public function testExplicitEmptyListDisablesTheDefault(): void
    {
        $config = $this->process([
            ['sql_adapter' => ['denied_tables' => [], 'denied_columns' => []]],
        ]);

        $this->assertSame([], $config['sql_adapter']['denied_tables']);
        $this->assertSame([], $config['sql_adapter']['denied_columns']);
    }

    public function testNonStringDeniedTableEntryIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/denied_tables.*must be a string/i');

        $this->process([
            ['sql_adapter' => ['denied_tables' => [123]]],
        ]);
    }

    public function testNonStringDeniedColumnEntryIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/denied_columns.*must be a string/i');

        $this->process([
            ['sql_adapter' => ['denied_columns' => [true]]],
        ]);
    }
}
