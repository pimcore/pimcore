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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterHintProviderInterface;

/**
 * Doctrine-based messenger transport definition.
 *
 * Writes PIMCORE_MESSENGER_TRANSPORT_DSN=doctrine://default?queue_name=
 * This is the default transport for all Pimcore messenger queues.
 * No user input is needed — the Doctrine connection is already
 * configured via DATABASE_URL.
 *
 * The trailing ?queue_name= is required because Pimcore appends
 * queue names directly to this value in bundle YAML configs.
 */
final readonly class DoctrineMessengerEnvVarDefinition implements
    MessengerTransportDefinitionInterface,
    ParameterHintProviderInterface
{
    public function getKey(): string
    {
        return 'messenger-doctrine';
    }

    public function getLabel(): string
    {
        return 'Messenger Transport (Doctrine)';
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getSectionName(): string
    {
        return 'pimcore/pimcore';
    }

    public function getParameters(): array
    {
        return [];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_MESSENGER_TRANSPORT_DSN' => 'doctrine://default?queue_name=',
        ];
    }

    public function validate(array $collectedValues): array
    {
        return [];
    }

    public function getParameterHint(string $envVarName, array $collectedSoFar): ?string
    {
        return "The messenger transport DSN includes a trailing separator for queue name\n"
            . "concatenation. Pimcore appends queue names directly to this value.\n"
            . "\n"
            . 'Doctrine format: doctrine://default?queue_name=';
    }
}
