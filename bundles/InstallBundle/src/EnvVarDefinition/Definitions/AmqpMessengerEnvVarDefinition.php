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
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterHintProviderInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

/**
 * AMQP-based messenger transport definition.
 *
 * Writes PIMCORE_MESSENGER_TRANSPORT_DSN with an AMQP URL.
 *
 * The DSN must end with a trailing "/" because Pimcore appends
 * queue names directly to this value in bundle YAML configs.
 */
final readonly class AmqpMessengerEnvVarDefinition implements
    MessengerTransportDefinitionInterface,
    ParameterHintProviderInterface
{
    public function getKey(): string
    {
        return 'messenger-amqp';
    }

    public function getLabel(): string
    {
        return 'Messenger Transport (AMQP)';
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
                'AMQP DSN',
                ParameterType::Url,
                defaultValue: 'amqp://guest:guest@127.0.0.1:5672/%2f/',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_MESSENGER_TRANSPORT_DSN' => $collectedValues['PIMCORE_MESSENGER_TRANSPORT_DSN']
                ?? 'amqp://guest:guest@127.0.0.1:5672/%2f/',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $url = $collectedValues['PIMCORE_MESSENGER_TRANSPORT_DSN'] ?? '';

        $validator = new FormatValidator();
        $validator
            ->requireNonEmpty($url, 'AMQP DSN')
            ->requireUrlWithScheme($url, 'AMQP', ['amqp', 'amqps']);

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        if (!str_ends_with($url, '/')) {
            return [
                'AMQP DSN must end with "/" for queue name concatenation'
                . ' (e.g. amqp://guest:guest@127.0.0.1:5672/%2f/).',
            ];
        }

        return $this->testConnection($url);
    }

    public function getParameterHint(string $envVarName, array $collectedSoFar): string
    {
        return "The messenger transport DSN must end with a trailing \"/\" for queue name\n"
            . "concatenation. Pimcore appends queue names directly to this value.\n"
            . "\n"
            . 'AMQP format: amqp://user:pass@host:5672/%2f/';
    }

    private function testConnection(string $url): array
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '127.0.0.1';
        $port = $parsed['port'] ?? 5672;

        $socket = @fsockopen($host, $port, $errno, $errstr, 3.0);
        if ($socket === false) {
            return [sprintf(
                'AMQP connection failed at %s:%d — %s.',
                $host,
                $port,
                $errstr,
            )];
        }

        fclose($socket);

        return [];
    }
}
