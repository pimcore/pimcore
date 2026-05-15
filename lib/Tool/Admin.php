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

namespace Pimcore\Tool;

use Exception;
use Pimcore;
use Pimcore\Event\SystemEvents;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Tool\Text\Csv;
use stdClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @internal
 */
class Admin
{
    /**
     * finds installed languages
     */
    public static function getLanguages(): array
    {
        $languageDirs = [];
        $translatedLanguages = [];

        $container = Pimcore::getContainer();

        $appDefaultPath = $container->getParameter('translator.default_path');

        if (is_dir($appDefaultPath)) {
            $languageDirs[] = $appDefaultPath;
        }

        $localeService = $container->get(LocaleServiceInterface::class);

        foreach ($languageDirs as $filesDir) {
            $files = scandir($filesDir);

            if ($files === false) {
                continue;
            }
            foreach ($files as $file) {
                $filePath = $filesDir . '/' . $file;

                if (!is_file($filePath)) {
                    continue;
                }

                $parts = explode('.', $file);

                if (count($parts) < 2) {
                    continue;
                }

                $languageCode = $parts[0];

                if ($parts[0] === 'admin' && isset($parts[1])) {
                    $languageCode = $parts[1];
                }

                $extension = end($parts);

                if ($extension === 'json' || $parts[0] === 'admin') {
                    if ($localeService->isLocale($languageCode)) {
                        $translatedLanguages[] = $languageCode;
                    }
                }
            }
        }

        return array_unique($translatedLanguages);
    }

    public static function determineCsvDialect(string $file): stdClass
    {
        // minimum 10 lines, to be sure take more
        $sample = '';
        for ($i = 0; $i < 10; $i++) {
            $sample .= implode('', array_slice(file($file), 0, 11)); // grab 20 lines
        }

        try {
            $sniffer = new Csv();
            $dialect = $sniffer->detect($sample);
        } catch (Exception $e) {
            // use default settings
            $dialect = new stdClass();
            $dialect->delimiter = ';';
            $dialect->quotechar = '"';
            $dialect->escapechar = '\\';
        }

        // validity check
        if (!in_array($dialect->delimiter, [';', ',', "\t", '|', ':'])) {
            $dialect->delimiter = ';';
        }

        return $dialect;
    }

    public static function getCurrentUser(): ?User
    {
        return Pimcore::getContainer()
            ->get(TokenStorageUserResolver::class)
            ->getUser();
    }

    public static function reorderWebsiteLanguages(User $user, array|string $languages, bool $returnLanguageArray = false): array|string
    {
        if (!is_array($languages)) {
            $languages = explode(',', $languages);
        }

        $contentLanguages = $user->getContentLanguages();
        if ($contentLanguages) {
            $contentLanguages = array_intersect($contentLanguages, $languages);
            $newLanguages = array_diff($languages, $contentLanguages);
            $languages = array_merge($contentLanguages, $newLanguages);
        }

        if (in_array('default', $languages)) {
            $languages = array_diff($languages, ['default']);
            array_unshift($languages, 'default');
        }
        if ($returnLanguageArray) {
            return $languages;
        }

        return implode(',', $languages);
    }
}
