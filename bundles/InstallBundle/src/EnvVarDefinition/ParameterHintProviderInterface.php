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
 * Optional interface for env var definitions that need to display
 * context-aware hints before specific parameter prompts.
 *
 * When a definition implements this interface, the ParameterCollector
 * calls getParameterHint() before each interactive prompt, passing
 * the values already collected for preceding parameters within the
 * same definition. This enables hints that depend on earlier input
 * (e.g., displaying a registration URL computed from a previously
 * collected encryption secret).
 */
interface ParameterHintProviderInterface
{
    /**
     * Return a contextual hint to display before prompting for a parameter.
     *
     * @param string $envVarName the parameter about to be prompted
     * @param array<string, string> $collectedSoFar values collected for earlier parameters in this definition
     *
     * @return string|null hint text to display, or null for no hint
     */
    public function getParameterHint(string $envVarName, array $collectedSoFar): ?string;
}
