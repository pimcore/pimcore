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

namespace Pimcore\Bundle\InstallBundle\Collector;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Collects parameter values for env var definitions using the priority chain:
 * 1. Existing env vars (already set in the environment)
 * 2. Interactive prompts (when running interactively)
 * 3. Default values (fallback)
 *
 * For definitions that resolve a final DSN env var (e.g., DATABASE_URL):
 * if the final env var is already set, all transient parameters are skipped.
 *
 * @internal
 */
final readonly class ParameterCollector
{
    public function __construct(
        private EnvVarReaderInterface $envVarReader,
    ) {
    }

    /**
     * Collect values for a single definition.
     *
     * Returns null if the user declined an optional definition
     * (answered "no" to gate prompt) or if a non-interactive optional
     * definition has no env vars set.
     *
     * @return array<string, string>|null collected values, or null if skipped
     */
    public function collect(
        EnvVarDefinitionInterface $definition,
        SymfonyStyle $io,
        bool $interactive,
    ): ?array {
        $resolvedEnvVarNames = $this->getResolvedEnvVarNames($definition);

        // Optional definition gate
        if (!$definition->isRequired()) {
            if (!$this->shouldConfigureOptional($definition, $io, $interactive, $resolvedEnvVarNames)) {
                return null;
            }
        }

        $parameters = $definition->getParameters();

        // Check if any resolved (final) env var is already fully set.
        // If all final env vars are present, we can skip transient parameter prompts.
        $allFinalVarsPresent = $this->allFinalEnvVarsPresent($resolvedEnvVarNames);

        $collectedValues = [];
        foreach ($parameters as $parameter) {
            // If this is a transient param and all final DSN vars are already set, skip it
            if ($parameter->isTransient() && $allFinalVarsPresent) {
                continue;
            }

            $value = $this->collectParameter($parameter, $io, $interactive);
            $collectedValues[$parameter->getEnvVarName()] = $value;
        }

        // If we skipped transients because final vars were present,
        // we need to populate collected values from those final vars
        // so validation can work correctly.
        if ($allFinalVarsPresent) {
            foreach ($resolvedEnvVarNames as $envVarName) {
                if (array_key_exists($envVarName, $collectedValues)) {
                    continue;
                }
                $envValue = $this->envVarReader->get($envVarName);
                if ($envValue !== null) {
                    $collectedValues[$envVarName] = $envValue;
                }
            }
        }

        return $collectedValues;
    }

    /**
     * @param list<string> $resolvedEnvVarNames
     */
    private function shouldConfigureOptional(
        EnvVarDefinitionInterface $definition,
        SymfonyStyle $io,
        bool $interactive,
        array $resolvedEnvVarNames,
    ): bool {
        // Check if any env var for this definition is already set
        foreach ($definition->getParameters() as $parameter) {
            if ($this->envVarReader->get($parameter->getEnvVarName()) !== null) {
                return true;
            }
        }

        // Also check resolved env var names
        foreach ($resolvedEnvVarNames as $name) {
            if ($this->envVarReader->get($name) !== null) {
                return true;
            }
        }

        if (!$interactive) {
            // No env vars set and non-interactive — skip
            return false;
        }

        // Ask the user
        return $io->confirm(
            sprintf('Configure %s?', $definition->getLabel()),
            false,
        );
    }

    private function collectParameter(
        ConfigParameter $parameter,
        SymfonyStyle $io,
        bool $interactive,
    ): string {
        $envValue = $this->envVarReader->get($parameter->getEnvVarName());

        if ($interactive) {
            return $this->promptForParameter($parameter, $io, $envValue);
        }

        // Non-interactive: env var takes precedence, then default, then empty
        if ($envValue !== null) {
            return $envValue;
        }

        return $parameter->getDefaultValue() ?? '';
    }

    /**
     * Prompt the user for a parameter value.
     *
     * If an env var value is already set, it is offered as the pre-filled
     * default so the user can press Enter to accept or type a new value.
     * For secrets, the existing value is not displayed but the user is
     * informed that a value is already configured.
     */
    private function promptForParameter(
        ConfigParameter $parameter,
        SymfonyStyle $io,
        ?string $envValue,
    ): string {
        if ($parameter->getDescription() !== null) {
            $io->text('<info>' . $parameter->getDescription() . '</info>');
        }

        $suggestion = $envValue ?? $parameter->getDefaultValue();

        if ($parameter->getType() === ParameterType::Secret) {
            if ($envValue !== null) {
                $io->text(sprintf('  <comment>%s is already configured. Press Enter to keep it.</comment>', $parameter->getEnvVarName()));
            }

            $input = $io->askHidden($parameter->getLabel());

            // Empty input (Enter) with pre-existing value → keep it
            if (($input === null || $input === '') && $envValue !== null) {
                return $envValue;
            }

            return (string) $input;
        }

        return match ($parameter->getType()) {
            ParameterType::Choice => (string) $io->choice(
                $parameter->getLabel(),
                $parameter->getChoices(),
                $suggestion,
            ),
            ParameterType::Boolean => $io->confirm(
                $parameter->getLabel(),
                ($suggestion ?? 'false') === 'true',
            ) ? 'true' : 'false',
            default => (string) $io->ask(
                $parameter->getLabel(),
                $suggestion,
            ),
        };
    }

    /**
     * Get env var names that resolveEnvVars() would produce.
     * We need this to check if the final DSN is already set.
     *
     * @return list<string>
     */
    private function getResolvedEnvVarNames(EnvVarDefinitionInterface $definition): array
    {
        // Build a dummy values array with defaults to discover which env vars get resolved
        $dummyValues = [];
        foreach ($definition->getParameters() as $param) {
            $dummyValues[$param->getEnvVarName()] = $param->getDefaultValue() ?? '';
        }

        return array_keys($definition->resolveEnvVars($dummyValues));
    }

    /**
     * @param list<string> $envVarNames
     */
    private function allFinalEnvVarsPresent(array $envVarNames): bool
    {
        if ($envVarNames === []) {
            return false;
        }

        foreach ($envVarNames as $name) {
            if ($this->envVarReader->get($name) === null) {
                return false;
            }
        }

        return true;
    }
}
