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

interface EnvVarDefinitionInterface
{
    /** Unique key, e.g. 'database', 'redis', 'mercure', 'search-engine' */
    public function getKey(): string;

    /** Human-readable label for CLI output */
    public function getLabel(): string;

    public function isRequired(): bool;

    /**
     * Section name for .env.local section markers.
     * Uses Composer package name convention, e.g. 'pimcore/pimcore'
     */
    public function getSectionName(): string;

    /**
     * Parameter definitions to collect from the user.
     *
     * @return list<ConfigParameter>
     */
    public function getParameters(): array;

    /**
     * Transform collected parameter values into final env vars.
     * This is where DSN assembly happens.
     * Transient parameters (used for prompting) are combined into
     * compound DSN values here.
     *
     * @param array<string, string> $collectedValues
     *
     * @return array<string, string> env var name => value
     */
    public function resolveEnvVars(array $collectedValues): array;

    /**
     * Validate collected values beyond type checks.
     * E.g., test DB connection, ping Redis, verify URL reachability.
     *
     * This method MUST always run — even in --no-interaction mode —
     * when the definition is being configured. It is the definition's
     * opportunity to verify that the infrastructure service is reachable
     * and correctly configured.
     *
     * This method is NOT called when an optional definition is skipped
     * (i.e., when the ParameterCollector returns null for a definition
     * with isRequired() === false).
     *
     * Returns array of error messages (empty = valid).
     *
     * @param array<string, string> $collectedValues
     *
     * @return list<string> error messages, empty if valid
     */
    public function validate(array $collectedValues): array;
}
