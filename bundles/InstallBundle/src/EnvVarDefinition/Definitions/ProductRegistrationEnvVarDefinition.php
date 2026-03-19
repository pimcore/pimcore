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

use Defuse\Crypto\Key;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterHintProviderInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;
use Pimcore\ProductRegistration\RegistrationValidator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Uid\Uuid;

/**
 * Handles encryption secret, instance identifier, and product key.
 *
 * The encryption secret and instance identifier are auto-generated when not
 * provided, allowing CI/automation to skip them. The product key must be
 * obtained from https://license.pimcore.com after registration with the
 * generated secret and instance identifier.
 */
final class ProductRegistrationEnvVarDefinition implements EnvVarDefinitionInterface, ParameterHintProviderInterface
{
    private readonly string $defaultEncryptionSecret;

    private readonly string $defaultInstanceIdentifier;

    public function __construct()
    {
        $this->defaultEncryptionSecret = $this->generateEncryptionSecret();
        $this->defaultInstanceIdentifier = Uuid::v6()->toBase58();
    }

    public function getKey(): string
    {
        return 'product-registration';
    }

    public function getLabel(): string
    {
        return 'Product Registration';
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
                'PIMCORE_ENCRYPTION_SECRET',
                'Encryption Secret',
                ParameterType::Secret,
                defaultValue: $this->defaultEncryptionSecret,
                description: 'Defuse encryption key. Auto-generated if not provided.',
            ),
            new ConfigParameter(
                'PIMCORE_INSTANCE_IDENTIFIER',
                'Instance Identifier',
                ParameterType::String,
                defaultValue: $this->defaultInstanceIdentifier,
                description: 'Unique instance identifier. Auto-generated if not provided.',
            ),
            new ConfigParameter(
                'PIMCORE_PRODUCT_KEY',
                'Product Key',
                ParameterType::Secret,
                description: 'Product key obtained from https://license.pimcore.com',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_ENCRYPTION_SECRET' => $collectedValues['PIMCORE_ENCRYPTION_SECRET'] ?? '',
            'PIMCORE_INSTANCE_IDENTIFIER' => $collectedValues['PIMCORE_INSTANCE_IDENTIFIER'] ?? '',
            'PIMCORE_PRODUCT_KEY' => $collectedValues['PIMCORE_PRODUCT_KEY'] ?? '',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $validator = new FormatValidator();

        $secret = $collectedValues['PIMCORE_ENCRYPTION_SECRET'] ?? '';
        $instanceIdentifier = $collectedValues['PIMCORE_INSTANCE_IDENTIFIER'] ?? '';
        $productKey = $collectedValues['PIMCORE_PRODUCT_KEY'] ?? '';

        $validator
            ->requireNonEmpty($secret, 'Encryption secret')
            ->requireNonEmpty($instanceIdentifier, 'Instance identifier')
            ->requireNonEmpty($productKey, 'Product key');

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        $errors = $this->validateEncryptionSecret($secret);
        if ($errors !== []) {
            return $errors;
        }

        return $this->validateProductKey($secret, $instanceIdentifier, $productKey);
    }

    public function getParameterHint(string $envVarName, array $collectedSoFar): ?string
    {
        if ($envVarName !== 'PIMCORE_PRODUCT_KEY') {
            return null;
        }

        $secret = $collectedSoFar['PIMCORE_ENCRYPTION_SECRET'] ?? '';
        $instanceIdentifier = $collectedSoFar['PIMCORE_INSTANCE_IDENTIFIER'] ?? '';

        if ($secret === '' || $instanceIdentifier === '') {
            return null;
        }

        try {
            Key::loadFromAsciiSafeString($secret);
        } catch (\Exception) {
            return null;
        }

        $instanceHash = hash_hmac('sha256', $instanceIdentifier, $secret);

        return sprintf(
            "Register your product to obtain a product key:\n"
            . 'https://license.pimcore.com/register?instance_identifier=%s&instance_hash=%s',
            $instanceIdentifier,
            $instanceHash,
        );
    }

    private function validateEncryptionSecret(string $secret): array
    {
        try {
            Key::loadFromAsciiSafeString($secret);
        } catch (\Exception $e) {
            return [sprintf(
                'Invalid encryption secret: %s. '
                . 'Run "vendor/bin/generate-defuse-key" to generate a valid key.',
                $e->getMessage(),
            )];
        }

        return [];
    }

    private function validateProductKey(
        string $secret,
        string $instanceIdentifier,
        string $productKey,
    ): array {
        try {
            $registrationValidator = new RegistrationValidator($secret, $instanceIdentifier);
            $registrationValidator->validateProductKey($productKey);
        } catch (InvalidConfigurationException $e) {
            return [$e->getMessage()];
        } catch (\Exception $e) {
            return [sprintf('Product key validation failed: %s', $e->getMessage())];
        }

        return [];
    }

    private function generateEncryptionSecret(): string
    {
        try {
            return Key::createNewRandomKey()->saveToAsciiSafeString();
        } catch (\Exception) {
            return '';
        }
    }
}
