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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Service;

use Pimcore\Bundle\ApplicationLoggerBundle\Enum\LogLevel;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function getTranslatedLogLevels(): array
    {
        $logLevels = LogLevel::cases();
        $translatedLogLevels = [];

        foreach ($logLevels as $logLevel) {
            $translatedValue = $this->getTranslatedLogLevel($logLevel->value);
            $translatedLogLevels[] = [
                'key' => $logLevel->value,
                'value' => $translatedValue,
            ];
        }

        return $translatedLogLevels;
    }

    public function getTranslatedLogLevel(int $key): string
    {
        return $this->translator->trans(
            'application_logger_log_level_' . $key,
            [],
            'admin'
        );
    }
}
