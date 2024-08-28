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

namespace Pimcore\Tool;

use Exception;
use Locale;
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
        $baseResource = Pimcore::getContainer()->getParameter('pimcore_admin.translations.path');
        $languageDir = Pimcore::getKernel()->locateResource($baseResource);
        $adminLanguages = Pimcore::getContainer()->getParameter('pimcore_admin.admin_languages');
        $appDefaultPath = Pimcore::getContainer()->getParameter('translator.default_path');

        $languageDirs = [$languageDir, $appDefaultPath];
        $translatedLanguages = [];
        foreach ($languageDirs as $filesDir) {
            if (is_dir($filesDir)) {
                $files = scandir($filesDir);
                foreach ($files as $file) {
                    if (is_file($filesDir . '/' . $file)) {
                        $parts = explode('.', $file);

                        $languageCode = $parts[0];
                        if ($parts[0] === 'admin') {
                            // this is for the app specific translations
                            $languageCode = $parts[1];
                        }

                        if ($parts[1] === 'json' || $parts[0] === 'admin') {
                            if (Pimcore::getContainer()->get(LocaleServiceInterface::class)->isLocale($languageCode)) {
                                $translatedLanguages[] = $languageCode;
                            }
                        }
                    }
                }
            }
        }

        $languages = [];
        foreach ($adminLanguages as $adminLanguage) {
            if (in_array($adminLanguage, $translatedLanguages, true) || in_array(Locale::getPrimaryLanguage($adminLanguage), $translatedLanguages, true)) {
                $languages[] = $adminLanguage;
            }
        }

        if (empty($languages)) {
            $languages = $translatedLanguages;
        }

        return array_unique($languages);
    }

    public static function getMinimizedScriptPath(string $scriptContent): array
    {
        $scriptPath = 'minified_javascript_core_'.md5($scriptContent).'.js';

        $storage = Storage::get('admin');
        $storage->write($scriptPath, $scriptContent);

        $params = [
            'storageFile' => basename($scriptPath),
            '_dc' => \Pimcore\Version::getRevision(),
        ];

        return $params;
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

    /**
     * @deprecated and will be removed in Pimcore 12
     */
    public static function getMaintenanceModeFile(): string
    {
        return PIMCORE_CONFIGURATION_DIRECTORY . '/maintenance.php';
    }

    /**
     * @deprecated and will be removed in Pimcore 12
     */
    public static function getMaintenanceModeScheduleLoginFile(): string
    {
        return PIMCORE_CONFIGURATION_DIRECTORY . '/maintenance-schedule-login.php';
    }

    /**
     * @deprecated Use MaintenanceModeHelper::activate instead.
     *
     * @throws Exception
     */
    public static function activateMaintenanceMode(?string $sessionId): void
    {
        if (empty($sessionId)) {
            $sessionId = Pimcore::getContainer()->get('request_stack')->getSession()->getId();
        }

        if (empty($sessionId)) {
            throw new Exception("It's not possible to activate the maintenance mode without a session-id");
        }

        File::putPhpFile(self::getMaintenanceModeFile(), to_php_data_file_format([
            'sessionId' => $sessionId,
        ]));

        @chmod(self::getMaintenanceModeFile(), 0666); // so it can be removed also via FTP, ...

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_ACTIVATE);
    }

    /**
     * @deprecated Use MaintenanceModeHelperInterface::deactivate instead.
     */
    public static function deactivateMaintenanceMode(): void
    {
        @unlink(self::getMaintenanceModeFile());

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_DEACTIVATE);
    }

    /**
     * @deprecated use MaintenanceModeHelperInterface::isActive instead.
     */
    public static function isInMaintenanceMode(): bool
    {
        $file = self::getMaintenanceModeFile();

        if (is_file($file)) {
            trigger_deprecation(
                'pimcore/pimcore',
                '11.1',
                sprintf(
                    "Calling Admin::activateMaintenanceMode or using maintenance mode file %s is deprecated.
                    \tUse MaintenanceModeHelperInterface::active instead.", $file)
            );
            $conf = include($file);
            if (isset($conf['sessionId'])) {
                return true;
            } else {
                @unlink($file);
            }
        }

        return false;
    }

    /**
     * @deprecated and will be removed in Pimcore 12
     */
    public static function isMaintenanceModeScheduledForLogin(): bool
    {
        $file = self::getMaintenanceModeScheduleLoginFile();

        if (is_file($file)) {
            trigger_deprecation(
                'pimcore/pimcore',
                '11.1',
                sprintf(
                    "Calling Admin::scheduleMaintenanceModeOnLogin or using maintenance mode file %s is deprecated.
                    \tThe maintenance mode schedule on login will not work in Pimcore 12", $file)
            );
            $conf = include($file);
            if (isset($conf['schedule']) && $conf['schedule']) {
                return true;
            } else {
                @unlink($file);
            }
        }

        return false;
    }

    /**
     * @deprecated and will be removed in Pimcore 12
     */
    public static function scheduleMaintenanceModeOnLogin(): void
    {
        File::putPhpFile(self::getMaintenanceModeScheduleLoginFile(), to_php_data_file_format([
            'schedule' => true,
        ]));

        @chmod(self::getMaintenanceModeScheduleLoginFile(), 0666); // so it can be removed also via FTP, ...

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_SCHEDULE_LOGIN);
    }

    /**
     * @deprecated and will be removed in Pimcore 12
     */
    public static function unscheduleMaintenanceModeOnLogin(): void
    {
        @unlink(self::getMaintenanceModeScheduleLoginFile());

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_UNSCHEDULE_LOGIN);
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
