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
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

final readonly class MailerEnvVarDefinition implements EnvVarDefinitionInterface
{
    private const ALLOWED_SCHEMES = ['smtp', 'smtps', 'sendmail', 'native', 'null'];

    public function getKey(): string
    {
        return 'mailer';
    }

    public function getLabel(): string
    {
        return 'Mailer (SMTP)';
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function getSectionName(): string
    {
        return 'symfony/mailer';
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                'MAILER_DSN',
                'Mailer DSN',
                ParameterType::Url,
                defaultValue: 'null://null',
                description: 'Symfony Mailer DSN (smtp://user:pass@host:port)',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'MAILER_DSN' => $collectedValues['MAILER_DSN'] ?? 'null://null',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $dsn = $collectedValues['MAILER_DSN'] ?? '';
        $label = 'Mailer DSN';

        $validator = new FormatValidator();
        $validator->requireNonEmpty($dsn, $label);

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        $validator->requireUrlWithScheme($dsn, $label, self::ALLOWED_SCHEMES);

        return $validator->getErrors();
    }
}
