<?php
declare(strict_types=1);

namespace Pimcore\Tests\Unit\InstallBundle\Support;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;

/**
 * Lightweight messenger transport definition for tests that don't care about transport.
 * Always passes validation and resolves to a dummy DSN.
 *
 * @internal
 */
final readonly class NoopMessengerTransportDefinition implements MessengerTransportDefinitionInterface
{
    public function getKey(): string
    {
        return 'noop-messenger-transport';
    }

    public function getLabel(): string
    {
        return 'Noop Messenger Transport';
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getSectionName(): string
    {
        return 'test';
    }

    public function getParameters(): array
    {
        return [];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return ['NOOP_MESSENGER_DSN' => 'doctrine://default?queue_name='];
    }

    public function validate(array $collectedValues): array
    {
        return [];
    }
}
