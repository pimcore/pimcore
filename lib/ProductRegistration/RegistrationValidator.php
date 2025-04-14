<?php
declare(strict_types=1);

namespace Pimcore\ProductRegistration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

readonly class RegistrationValidator
{

    public function __construct(
        private string $secret
    ) {
    }

    public function getInstanceIdentifier() {
        return sha1(substr($this->secret, 3, -3));
    }

    public function validateProductKey(?string $productKey): void
    {
        $pleaseRegisterMessage =
            "Please register your product via " .
            "https://license.pimcore.com/register?instance_identifier={$this->getInstanceIdentifier()} " .
            "and provide the Product Key.";

        if (empty($productKey)) {
            throw new InvalidConfigurationException(
                'Your Product Key is empty. ' . $pleaseRegisterMessage
            );
        }

        $publicKey = file_get_contents(__DIR__ . '/pimcore-productregistration-ec_public.pem');
        $decodedSignature = json_decode(base64_decode($productKey), true);

        if(!$decodedSignature) {
            throw new InvalidConfigurationException(
                'Your Product Key is invalid. ' . $pleaseRegisterMessage
            );
        }

        $payload = json_decode($decodedSignature['payload'] ?? null, true);

        if($payload && ($payload['id'] ?? null) !== $this->getInstanceIdentifier()) {
            throw new InvalidConfigurationException(
                'Your Instance Identifier does not match with your Product Key: ' .
                $payload['id'] . ' vs ' . $this->getInstanceIdentifier()
            );
        }

        if(1 !== openssl_verify(
            $decodedSignature['payload'],
            base64_decode($decodedSignature['signature']),
            $publicKey,
            OPENSSL_ALGO_SHA256)) {

            throw new InvalidConfigurationException(
                'Your Product Key is invalid. ' . $pleaseRegisterMessage
            );

        }
    }
}
