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
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

/**
 * RabbitMQ-based messenger transport definition.
 *
 * Writes PIMCORE_MESSENGER_TRANSPORT_DSN with an AMQP URL.
 *
 * @internal
 */
final readonly class RabbitMqMessengerEnvVarDefinition implements MessengerTransportDefinitionInterface
{
    public function getKey(): string
    {
        return 'messenger-rabbitmq';
    }

    public function getLabel(): string
    {
        return 'Messenger Transport (RabbitMQ)';
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
        return [
            new ConfigParameter(
                'PIMCORE_MESSENGER_TRANSPORT_DSN',
                'RabbitMQ URL',
                ParameterType::Url,
                defaultValue: 'amqp://guest:guest@127.0.0.1:5672/%2f',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_MESSENGER_TRANSPORT_DSN' => $collectedValues['PIMCORE_MESSENGER_TRANSPORT_DSN']
                ?? 'amqp://guest:guest@127.0.0.1:5672/%2f',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $url = $collectedValues['PIMCORE_MESSENGER_TRANSPORT_DSN'] ?? '';

        $validator = new FormatValidator();
        $validator
            ->requireNonEmpty($url, 'RabbitMQ URL')
            ->requireUrlWithScheme($url, 'RabbitMQ', ['amqp', 'amqps']);

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        return $this->testConnection($url);
    }

    private function testConnection(string $url): array
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '127.0.0.1';
        $port = $parsed['port'] ?? 5672;

        $socket = @fsockopen($host, $port, $errno, $errstr, 3.0);
        if ($socket === false) {
            return [sprintf(
                'RabbitMQ connection failed at %s:%d — %s.',
                $host,
                $port,
                $errstr,
            )];
        }

        fclose($socket);

        return [];
    }
}
