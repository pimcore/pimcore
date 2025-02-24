<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\ApplicationLoggerBundle\Service;

use Pimcore\Bundle\ApplicationLoggerBundle\Enum\LogLevel;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        private TranslatorInterface $translator
    )
    {
    }

    public function getTranslatedLogLevels(): array
    {
        $logLevels = LogLevel::cases();
        $translatedLogLevels = [];

        foreach ($logLevels as $logLevel) {
            $translatedValue = $this->getTranslatedLogLevel($logLevel->value);
            $translatedLogLevels[] = [
                'key' => $logLevel->value,
                'value' => $translatedValue
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
