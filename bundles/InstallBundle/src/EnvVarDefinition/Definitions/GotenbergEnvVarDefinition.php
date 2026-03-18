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

namespace Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

/**
 * @internal
 */
final readonly class GotenbergEnvVarDefinition implements EnvVarDefinitionInterface
{
    public function getKey(): string
    {
        return 'gotenberg';
    }

    public function getLabel(): string
    {
        return 'Gotenberg (PDF Generation)';
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function getSectionName(): string
    {
        return 'pimcore/pimcore';
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                'GOTENBERG_BASE_URL',
                'Gotenberg Base URL',
                ParameterType::Url,
                defaultValue: 'http://gotenberg:3000',
                description: 'Base URL of the Gotenberg service for PDF generation',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'GOTENBERG_BASE_URL' => $collectedValues['GOTENBERG_BASE_URL']
                ?? 'http://gotenberg:3000',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $validator = new FormatValidator();

        $url = $collectedValues['GOTENBERG_BASE_URL'] ?? '';

        $validator
            ->requireNonEmpty($url, 'Gotenberg base URL')
            ->requireValidUrl($url, 'Gotenberg base');

        return $validator->getErrors();
    }
}
