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

namespace Pimcore\Localization;

use Pimcore\Translation\Translator;
use ResourceBundle;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleService implements LocaleServiceInterface
{
    protected ?string $locale = null;

    protected ?RequestStack $requestStack = null;

    protected ?Translator $translator = null;

    public function __construct(RequestStack $requestStack = null, Translator $translator = null)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public function isLocale(string $locale): bool
    {
        $locales = array_flip($this->getLocaleList());
        $exists = isset($locales[$locale]);

        return $exists;
    }

    public function findLocale(): string
    {
        if ($requestLocale = $this->getLocaleFromRequest()) {
            return $requestLocale;
        }

        $defaultLocale = \Pimcore\Tool::getDefaultLanguage();
        if ($defaultLocale) {
            return $defaultLocale;
        }

        return '';
    }

    protected function getLocaleFromRequest(): ?string
    {
        if ($this->requestStack) {
            $mainRequest = $this->requestStack->getMainRequest();

            if ($mainRequest) {
                return $mainRequest->getLocale();
            }
        }

        return null;
    }

    public function getLocaleList(): array
    {
        return ResourceBundle::getLocales('');
    }

    public function getDisplayRegions(string $locale = null): array
    {
        if (!$locale) {
            $locale = $this->findLocale();
        }

        $dataPath = PIMCORE_COMPOSER_PATH . '/umpirsky/country-list/data/';
        if (file_exists($dataPath . $locale . '/country.php')) {
            $regions = include($dataPath . $locale . '/country.php');
        } else {
            $regions = include($dataPath . 'en/country.php');
        }

        return $regions;
    }

    public function getLocale(): ?string
    {
        if (null === $this->locale) {
            $this->locale = $this->getLocaleFromRequest();
        }

        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;

        if ($locale) {
            if ($this->requestStack) {
                $mainRequest = $this->requestStack->getMainRequest();
                if ($mainRequest) {
                    $mainRequest->setLocale($locale);
                }

                $currentRequest = $this->requestStack->getCurrentRequest();
                if ($currentRequest) {
                    $currentRequest->setLocale($locale);
                }
            }

            if ($this->translator) {
                $this->translator->setLocale($locale);
            }
        }
    }

    public function hasLocale(): bool
    {
        return $this->getLocale() !== null;
    }
}
