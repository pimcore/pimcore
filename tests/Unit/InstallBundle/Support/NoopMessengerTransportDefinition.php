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
