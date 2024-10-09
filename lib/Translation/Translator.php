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

namespace Pimcore\Translation;

use Exception;
use Pimcore\Cache;
use Pimcore\Model\Translation;
use Pimcore\Tool;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface, WarmableInterface
{
    protected TranslatorInterface|WarmableInterface|TranslatorBagInterface $translator;

    protected array $initializedCatalogues = [];

    protected string $adminPath = '';

    protected array $adminTranslationMapping = [];

    /**
     * If true, the translator will just return the translation key instead of actually translating
     * the message. Can be useful for debugging and to get an overview over used translation keys on
     * a page.
     *
     */
    protected bool $disableTranslations = false;

    protected Kernel $kernel;

    public function __construct(TranslatorInterface $translator)
    {
        if (!$translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException(sprintf('The Translator "%s" must implement TranslatorInterface and TranslatorBagInterface.', get_class($translator)));
        }

        $this->translator = $translator;
    }

    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        $id = trim($id);

        if ($this->disableTranslations) {
            return $id;
        }

        if (null === $domain) {
            $domain = Translation::DOMAIN_DEFAULT;
        }

        if ($domain === Translation::DOMAIN_ADMIN && !empty($this->adminTranslationMapping)) {
            if (null === $locale) {
                $locale = $this->getLocale();
            }

            if (array_key_exists($locale, $this->adminTranslationMapping)) {
                $locale = $this->adminTranslationMapping[$locale];
            }
        }

        $catalogue = $this->getCatalogue($locale);
        $locale = $catalogue->getLocale();

        $this->lazyInitialize($domain, $locale);

        $originalId = $id;
        $term = $this->translator->trans($id, $parameters, $domain, $locale);

        // only check for empty translation on original ID - we don't want to create empty
        // translations for normalized IDs when case insensitive
        $term = $this->checkForEmptyTranslation($originalId, $term, $parameters, $domain, $locale);

        // check for an indexed array, that used the ZF1 vsprintf() notation for parameters
        if (isset($parameters[0])) {
            $term = vsprintf($term, $parameters);
        }

        $term = $this->updateLinks($term);

        return $term;
    }

    public function setLocale(string $locale): void
    {
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }
    }

    public function getLocale(): string
    {
        if ($this->translator instanceof LocaleAwareInterface) {
            return $this->translator->getLocale();
        }

        return \Pimcore\Tool::getDefaultLanguage();
    }

    public function getCatalogue(string $locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * @return MessageCatalogueInterface[]
     */
    public function getCatalogues(): array
    {
        return $this->translator->getCatalogues();
    }

    /**
     * @internal
     *
     */
    public function lazyInitialize(string $domain, string $locale): void
    {
        $cacheKey = $this->getCacheKey($domain, $locale);

        if (isset($this->initializedCatalogues[$cacheKey])) {
            return;
        }

        $this->initializedCatalogues[$cacheKey] = true;

        if (Translation::isAValidDomain($domain)) {
            if (!$catalogue = Cache::load($cacheKey)) {
                $data = ['__pimcore_dummy' => 'only_a_dummy'];
                $dataIntl = ['__pimcore_dummy' => 'only_a_dummy'];

                $list = new Translation\Listing();
                $list->setDomain($domain);

                $debugAdminTranslations = \Pimcore\Config::getSystemConfiguration('general')['debug_admin_translations'] ?? false;
                $list->setCondition('language = ?', [$locale]);
                $translations = $list->loadRaw();

                foreach ($translations as $translation) {
                    $translationTerm = Tool\Text::removeLineBreaks($translation['text']);
                    if (
                        (!isset($data[$translation['key']]) && !$this->getCatalogue($locale)->has($translation['key'], $domain)) ||
                        !empty($translationTerm)) {
                        $translationKey = $translation['key'];

                        if (empty($translationTerm) && $debugAdminTranslations) {
                            //wrap non-translated keys with "+", if debug admin translations is enabled
                            $translationTerm = '+' . $translationKey. '+';
                        }

                        if (empty($translation['type']) || $translation['type'] === 'simple') {
                            $data[$translationKey] = $translationTerm;
                        } else {
                            $dataIntl[$translationKey] = $translationTerm;
                        }
                    }
                }

                $data = [
                    $domain => $data,
                    $domain.MessageCatalogue::INTL_DOMAIN_SUFFIX => $dataIntl,
                ];
                $catalogue = new MessageCatalogue($locale, $data);

                Cache::save($catalogue, $cacheKey, ['translator', 'translator_website', 'translate'], null, 999);
            }

            if ($catalogue) {
                $this->getCatalogue($locale)->addCatalogue($catalogue);
            }
        }
    }

    /**
     * Resets the initialization of a specific catalogue
     *
     *
     */
    public function resetInitialization(string $domain, string $locale): void
    {
        $cacheKey = $this->getCacheKey($domain, $locale);
        unset($this->initializedCatalogues[$cacheKey]);
    }

    /**
     * Reset Catalogues initialization
     */
    public function resetCache(): void
    {
        $this->initializedCatalogues = [];
    }

    /**
     * @throws Exception
     */
    private function checkForEmptyTranslation(string $id, string $translated, array $parameters, string $domain, string $locale): string
    {
        if (empty($id)) {
            return $translated;
        }

        $normalizedId = $id;

        //translate only plural form(seperated by pipe "|") with count param
        if (isset($parameters['%count%']) && $translated && str_contains($normalizedId, '|')) {
            $normalizedId = $id = $translated;
            $translated = $this->translator->trans($normalizedId, $parameters, $domain, $locale);
        }

        $lookForFallback = empty($translated);
        if ($normalizedId != $translated && $translated) {
            return $translated;
        } elseif ($normalizedId == $translated) {
            if ($this->getCatalogue($locale)->has($normalizedId, $domain)) {
                $translated = $this->getCatalogue($locale)->get($normalizedId, $domain);
                if ($normalizedId != $translated && $translated) {
                    return $translated;
                }
            } elseif (Translation::isAValidDomain($domain)) {
                if (strlen($id) > 190) {
                    throw new Exception("Message ID's longer than 190 characters are invalid!");
                }

                // no translation found create key
                if (Translation::IsAValidLanguage($domain, $locale)) {
                    $t = Translation::getByKey($id, $domain);
                    if ($t) {
                        if (!$t->hasTranslation($locale)) {
                            $t->addTranslation($locale, '');
                        } else {
                            // return the original not lowercased ID
                            return $id;
                        }
                    } else {
                        $t = new Translation();
                        $t->setDomain($domain);
                        $t->setKey($id);

                        // add all available languages
                        foreach (Translation::getValidLanguages() as $language) {
                            $t->addTranslation($language, '');
                        }
                    }

                    TranslationEntriesDumper::addToSaveQueue($t);
                }

                // put it into the catalogue, otherwise when there are more calls to the same key during one process
                // the key would be inserted/updated several times, what would be redundant
                $this->getCatalogue($locale)->set($normalizedId, $id, $domain);
            }
        }

        // now check for custom fallback locales, only for shared translations
        if ($lookForFallback && $domain == 'messages') {
            foreach (Tool::getFallbackLanguagesFor($locale) as $fallbackLanguage) {
                $this->lazyInitialize($domain, $fallbackLanguage);
                $catalogue = $this->getCatalogue($fallbackLanguage);

                $fallbackValue = '';

                if ($catalogue->has($normalizedId, $domain)) {
                    $fallbackValue = $catalogue->get($normalizedId, $domain);
                }

                if ($fallbackValue && $normalizedId != $fallbackValue) {
                    $isIntl = $catalogue->defines($normalizedId, $domain . $catalogue::INTL_DOMAIN_SUFFIX);
                    // update fallback value in original catalogue otherwise multiple calls to the same id will not work
                    $this->getCatalogue($locale)->set($normalizedId, $fallbackValue, $domain . ($isIntl ? $catalogue::INTL_DOMAIN_SUFFIX : ''));

                    return $this->translator->trans($normalizedId, $parameters, $domain, $locale);
                }
            }
        }

        return !empty($translated) ? $translated : $id;
    }

    /**
     * @internal
     *
     */
    public function getAdminPath(): string
    {
        return $this->adminPath;
    }

    /**
     *
     * @internal
     */
    public function setAdminPath(string $adminPath): void
    {
        $this->adminPath = $adminPath;
    }

    /**
     * @internal
     *
     */
    public function getAdminTranslationMapping(): array
    {
        return $this->adminTranslationMapping;
    }

    /**
     * @internal
     *
     */
    public function setAdminTranslationMapping(array $adminTranslationMapping): void
    {
        $this->adminTranslationMapping = $adminTranslationMapping;
    }

    /**
     * @internal
     *
     */
    public function getKernel(): Kernel
    {
        return $this->kernel;
    }

    /**
     *
     * @internal
     */
    public function setKernel(Kernel $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function getDisableTranslations(): bool
    {
        return $this->disableTranslations;
    }

    public function setDisableTranslations(bool $disableTranslations): void
    {
        $this->disableTranslations = $disableTranslations;
    }

    private function updateLinks(string $text): string
    {
        if (strpos($text, 'pimcore_id')) {
            $text = Tool\Text::wysiwygText($text);
        }

        return $text;
    }

    /**
     * Passes through all unknown calls onto the translator object.
     */
    public function __call(string $method, array $args): mixed
    {
        return call_user_func_array([$this->translator, $method], $args);
    }

    private function getCacheKey(string $domain, string $locale): string
    {
        return 'translation_data_' . md5($domain . '_' . $locale);
    }

    /**
     *
     * @return string[]
     */
    public function warmUp(string $cacheDir): array
    {
        return $this->translator->warmUp($cacheDir);
    }
}
