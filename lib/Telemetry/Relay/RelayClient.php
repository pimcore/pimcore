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

namespace Pimcore\Telemetry\Relay;

use Exception;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use function is_array;
use function json_decode;

/**
 * Direct HTTP delivery to the first-party relay. POSTs the cleartext outer envelope
 * `{instanceIdentifier, v, ciphertext}` - only the instance identifier is visible; the relay uses it
 * to look up the product key and decrypt the rest. Holds no PostHog key and no relay secret: the
 * encryption itself authenticates the instance.
 *
 * @internal
 */
final class RelayClient implements RelayClientInterface
{
    private const FORMAT_VERSION = 1;

    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly string $endpoint,
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->endpoint !== '';
    }

    public function send(string $instanceIdentifier, string $ciphertext): bool
    {
        if ($this->endpoint === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'json' => [
                    'instanceIdentifier' => $instanceIdentifier,
                    'v' => self::FORMAT_VERSION,
                    'ciphertext' => $ciphertext,
                ],
                'timeout' => self::TIMEOUT_SECONDS,
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();

            if ($status < 200 || $status >= 300) {
                $this->logger->warning('Telemetry relay rejected a batch', ['status' => $status]);

                return false;
            }

            $body = json_decode((string)$response->getBody(), true);

            // Success is a confirmed acceptance, not merely a 2xx - so a proxy 200 without the relay
            // body never causes us to drop an undelivered batch.
            return is_array($body) && ($body['status'] ?? null) === 'ok';
        } catch (Exception $exception) {
            $this->logger->error('Unable to send telemetry batch to the relay', [
                'endpoint' => $this->endpoint,
                'exception' => $exception,
            ]);

            return false;
        }
    }
}
