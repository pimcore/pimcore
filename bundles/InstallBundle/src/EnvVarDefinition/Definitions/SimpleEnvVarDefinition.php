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
 * Generic env var definition for simple pass-through parameters.
 *
 * Derives validation automatically from parameter types:
 * - Required parameters: non-empty check
 * - ParameterType::Url: URL format validation via parse_url()
 *
 * Use this for project-specific env vars that need no custom validation
 * beyond basic type-derived checks.
 */
final readonly class SimpleEnvVarDefinition implements EnvVarDefinitionInterface
{
    /**
     * @param list<ConfigParameter> $parameters
     */
    public function __construct(
        private string $key,
        private string $label,
        private string $sectionName,
        private array $parameters,
        private bool $required = true,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getSectionName(): string
    {
        return $this->sectionName;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        $envVars = [];

        foreach ($this->parameters as $parameter) {
            $name = $parameter->getEnvVarName();
            if (array_key_exists($name, $collectedValues)) {
                $envVars[$name] = $collectedValues[$name];
            }
        }

        return $envVars;
    }

    public function validate(array $collectedValues): array
    {
        $validator = new FormatValidator();

        foreach ($this->parameters as $parameter) {
            $name = $parameter->getEnvVarName();
            $value = $collectedValues[$name] ?? '';
            $paramLabel = $parameter->getLabel();

            if ($parameter->isRequired()) {
                $validator->requireNonEmpty($value, $paramLabel);
            }

            if ($value !== '' && $parameter->getType() === ParameterType::Url) {
                $validator->requireValidUrl($value, $paramLabel);
            }
        }

        return $validator->getErrors();
    }
}
