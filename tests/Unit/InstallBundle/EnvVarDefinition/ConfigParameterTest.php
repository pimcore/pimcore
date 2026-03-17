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

namespace Pimcore\Tests\Unit\InstallBundle\EnvVarDefinition;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class ConfigParameterTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $param = new ConfigParameter(
            'DATABASE_URL',
            'Database URL',
            ParameterType::Url,
        );

        $this->assertSame('DATABASE_URL', $param->getEnvVarName());
        $this->assertSame('Database URL', $param->getLabel());
        $this->assertSame(ParameterType::Url, $param->getType());
        $this->assertTrue($param->isRequired());
        $this->assertNull($param->getDefaultValue());
        $this->assertNull($param->getDescription());
        $this->assertSame([], $param->getChoices());
    }

    public function testConstructorWithAllValues(): void
    {
        $param = new ConfigParameter(
            'PIMCORE_SEARCH_ENGINE',
            'Search Engine',
            ParameterType::Choice,
            required: true,
            defaultValue: 'opensearch',
            description: 'Choose your search engine',
            choices: ['opensearch', 'elasticsearch'],
        );

        $this->assertSame('PIMCORE_SEARCH_ENGINE', $param->getEnvVarName());
        $this->assertSame(['opensearch', 'elasticsearch'], $param->getChoices());
        $this->assertSame('opensearch', $param->getDefaultValue());
    }

}
