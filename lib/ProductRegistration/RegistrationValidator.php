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

namespace Pimcore\ProductRegistration;

use Defuse\Crypto\Key;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Uid\Uuid;

/**
 * @internal
 */
final class RegistrationValidator
{
    private string $hashedInstanceIdentifier;

    public function __construct(
        public readonly string $secret,
        private ?string $instanceIdentifier,
    ) {
        //just to validate the secret to be a valid key
        Key::loadFromAsciiSafeString($secret);

        if (empty($this->instanceIdentifier)) {
            $this->instanceIdentifier = Uuid::v6()->toBase58();
        }
        $this->hashedInstanceIdentifier = hash_hmac('sha256', $this->instanceIdentifier, $secret);
    }

    public function getHashedInstanceIdentifier(): string
    {
        return $this->hashedInstanceIdentifier;
    }

    public function getInstanceIdentifier(): ?string
    {
        return $this->instanceIdentifier;
    }

    public function validateProductKey(?string $productKey): void
    {
        $pleaseRegisterMessage =
            'Please register your product via ' .
            'https://license.pimcore.com/register?' .
            "instance_identifier={$this->getInstanceIdentifier()}" .
            "&instance_hash={$this->getHashedInstanceIdentifier()}" .
            ' and provide the product key.';

        if (empty($productKey)) {
            throw new InvalidConfigurationException(
                'Your product key is empty. ' . $pleaseRegisterMessage
            );
        }

        $publicKey = file_get_contents(__DIR__ . '/pimcore-productregistration-ec_public.pem');
        $decodedSignature = json_decode(base64_decode($productKey), true);

        if (!$decodedSignature) {
            throw new InvalidConfigurationException(
                'Your product key is invalid. ' . $pleaseRegisterMessage
            );
        }

        $payload = json_decode($decodedSignature['payload'] ?? null, true);
        $hashedInstanceId = $payload['id'] ?? null;

        if ($hashedInstanceId !== $this->getHashedInstanceIdentifier()) {
            throw new InvalidConfigurationException(
                'Your hashed instance identifier does not match with your product key: ' .
                $hashedInstanceId . ' vs ' . $this->getHashedInstanceIdentifier() . ";\n" . $pleaseRegisterMessage
            );
        }

        if (1 !== openssl_verify(
            $decodedSignature['payload'],
            base64_decode($decodedSignature['signature']),
            $publicKey,
            OPENSSL_ALGO_SHA256)) {

            throw new InvalidConfigurationException(
                'Your product key is invalid. ' . $pleaseRegisterMessage
            );

        }
    }
}
