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

namespace Pimcore\Bundle\InstallBundle\EnvVarDefinition;

/**
 * @internal
 */
final readonly class ConfigParameter
{
    /**
     * @param list<string> $choices Available choices when type is ParameterType::Choice
     */
    public function __construct(
        private string $envVarName,
        private string $label,
        private ParameterType $type,
        private bool $required = true,
        private ?string $defaultValue = null,
        private ?string $description = null,
        private array $choices = [],
        private bool $transient = false,
    ) {
    }

    public function getEnvVarName(): string
    {
        return $this->envVarName;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): ParameterType
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    public function isTransient(): bool
    {
        return $this->transient;
    }
}
