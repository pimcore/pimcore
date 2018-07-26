<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Translation;

use Pimcore\Cache;
use Pimcore\Model\Translation\AbstractTranslation;
use Pimcore\Model\Translation\TranslationInterface;
use Pimcore\Tool;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface
{
    /**
     * @var TranslatorInterface|TranslatorBagInterface
     */
    protected $translator;

    /**
     * @var bool
     */
    protected $caseInsensitive = false;

    /**
     * @var array
     */
    protected $initializedCatalogues = [];

    /**
     * @var MessageSelector
     */
    private $selector;

    /**
     * @var string
     */
    protected $adminPath = '';

    /**
     * If true, the translator will just return the translation key instead of actually translating
     * the message. Can be useful for debugging and to get an overview over used translation keys on
     * a page.
     *
     * @var bool
     */
    protected $disableTranslations = false;

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * @param TranslatorInterface $translator The translator must implement TranslatorBagInterface
     * @param bool $caseInsensitive
     */
    public function __construct(TranslatorInterface $translator, bool $caseInsensitive = false)
    {
        if (!$translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException(sprintf('The Translator "%s" must implement TranslatorInterface and TranslatorBagInterface.', get_class($translator)));
        }

        $this->translator = $translator;
        $this->selector = new MessageSelector();

        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $id = trim($id);

        if ($this->disableTranslations) {
            return $id;
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        $catalogue = $this->getCatalogue($locale);
        $locale = $catalogue->getLocale();
        $this->lazyInitialize($domain, $locale);

        $term = $this->getFromCatalogue($catalogue, (string)$id, $domain, $locale);
        $term = strtr($term, $parameters);

        // check for an indexed array, that used the ZF1 vsprintf() notation for parameters
        if (isset($parameters[0])) {
            $term = vsprintf($term, $parameters);
        }

        return $term;
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        $id = trim($id);

        if ($this->disableTranslations) {
            return $id;
        }

        $parameters = array_merge([
            '%count%' => $number,
        ], $parameters);

        if (null === $domain) {
            $domain = 'messages';
        }

        $id = (string) $id;
        $catalogue = $this->getCatalogue($locale);
        $locale = $catalogue->getLocale();
        $this->lazyInitialize($domain, $locale);

        while (!$catalogue->defines($id, $domain)) {
            if ($cat = $catalogue->getFallbackCatalogue()) {
                $catalogue = $cat;
                $locale = $catalogue->getLocale();
            } else {
                break;
            }
        }

        $term = $this->getFromCatalogue($catalogue, $id, $domain, $locale);
        $term = $this->selector->choose($term, (int) $number, $locale);

        return strtr($term, $parameters);
    }

    protected function getFromCatalogue(MessageCatalogueInterface $catalogue, $id, $domain, $locale)
    {
        $originalId = $id;
        if ($this->caseInsensitive && in_array($domain, ['messages', 'admin'])) {
            $id = mb_strtolower($id);
        }

        $term = $catalogue->get($id, $domain);

        // only check for empty translation on original ID - we don't want to create empty
        // translations for normalized IDs when case insensitive
        $term = $this->checkForEmptyTranslation($originalId, $term, $domain, $locale);

        return $term;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogue($locale = null)
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * @param string $domain
     * @param string $locale
     */
    public function lazyInitialize($domain, $locale)
    {
        $cacheKey = 'translation_data_' . $domain . '_' . $locale;

        if (isset($this->initializedCatalogues[$cacheKey])) {
            return;
        }

        $this->initializedCatalogues[$cacheKey] = true;
        $backend = $this->getBackendForDomain($domain);

        if ($backend) {
            $catalogue = null;

            if (!$catalogue = Cache::load($cacheKey)) {
                $data = ['__pimcore_dummy' => 'only_a_dummy'];

                if ($domain == 'admin') {
                    $jsonFiles = [
                        $locale . '.json' => 'en.json',
                        $locale . '.extended.json' => 'en.extended.json'
                    ];

                    foreach ($jsonFiles as $sourceFile => $fallbackFile) {
                        try {
                            $jsonPath = $this->getKernel()->locateResource($this->getAdminPath() . '/' . $sourceFile);
                        } catch (\Exception $e) {
                            $jsonPath = $this->getKernel()->locateResource($this->getAdminPath() . '/' . $fallbackFile);
                        }

                        $jsonTranslations = json_decode(file_get_contents($jsonPath), true);
                        if (is_array($jsonTranslations)) {
                            $data = array_merge($jsonTranslations, $data);
                        }
                    }
                }

                $listClass = '\\Pimcore\\Model\\Translation\\' . ucfirst($backend) . '\\Listing';
                $list = new $listClass();

                $list->setCondition('language = ?', [$locale]);
                $translations = $list->loadRaw();

                foreach ($translations as $translation) {
                    $translationTerm = Tool\Text::removeLineBreaks($translation['text']);
                    if (
                        (!isset($data[$translation['key']]) && !$this->getCatalogue($locale)->has($translation['key'], $domain)) ||
                        !empty($translationTerm)) {
                        $translationKey = $translation['key'];

                        // store as case insensitive if configured
                        if ($this->caseInsensitive) {
                            $translationKey = mb_strtolower($translationKey);
                        }

                        $data[$translationKey] = $translationTerm;
                    }
                }

                // aliases support
                $aliasesPath = $this->getKernel()->locateResource($this->getAdminPath() . '/aliases.json');
                $aliases = json_decode(file_get_contents($aliasesPath), true);
                foreach ($aliases as $aliasTarget => $aliasSource) {
                    if (isset($data[$aliasSource]) && !isset($data[$aliasTarget])) {
                        $data[$aliasTarget] = $data[$aliasSource];
                    }
                }

                $data = [$domain => $data];
                $catalogue = new MessageCatalogue($locale, $data);

                Cache::save($catalogue, $cacheKey, ['translator', 'translator_website', 'translate'], null, 999);
            }

            if ($catalogue) {
                $this->getCatalogue($locale)->addCatalogue($catalogue);
            }
        }
    }

    /**
     * @param string $id
     * @param string $translated
     * @param string $domain
     * @param string $locale
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function checkForEmptyTranslation($id, $translated, $domain, $locale)
    {
        $normalizedId = $id;
        if ($this->caseInsensitive) {
            $normalizedId = mb_strtolower($id);
        }

        $lookForFallback = empty($translated);
        if (empty($id)) {
            return $translated;
        } elseif ($normalizedId != $translated && $translated) {
            return $translated;
        } elseif ($normalizedId == $translated && !$this->getCatalogue($locale)->has($normalizedId, $domain)) {
            $backend = $this->getBackendForDomain($domain);
            if ($backend) {
                if (strlen($id) > 190) {
                    throw new \Exception("Message ID's longer than 190 characters are invalid!");
                }

                /** @var TranslationInterface $class */
                $class = '\\Pimcore\\Model\\Translation\\' . ucfirst($backend);

                // no translation found create key
                if ($class::isValidLanguage($locale)) {
                    try {
                        /**
                         * @var AbstractTranslation $t
                         */
                        $t = $class::getByKey($id);
                        if (!$t->hasTranslation($locale)) {
                            $t->addTranslation($locale, '');
                        } else {
                            // return the original not lowercased ID
                            return $id;
                        }
                    } catch (\Exception $e) {
                        $t = new $class();
                        $t->setKey($id);

                        // add all available languages
                        $availableLanguages = (array)$class::getLanguages();
                        foreach ($availableLanguages as $language) {
                            $t->addTranslation($language, '');
                        }
                    }

                    $t->save();
                }

                // put it into the catalogue, otherwise when there are more calls to the same key during one process
                // the key would be inserted/updated several times, what would be redundant
                $this->getCatalogue($locale)->set($normalizedId, $id, $domain);

                $translated = $id; // use the original translation key, this is necessary if using case-insensitive configuration
                $lookForFallback = true;
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

                if ($fallbackValue) {
                    // update fallback value in original catalogue otherwise multiple calls to the same id will not work
                    $this->getCatalogue($locale)->set($normalizedId, $fallbackValue, $domain);

                    return $fallbackValue;
                }
            }

            return $id;
        }

        if (empty($translated)) {
            return $id;
        } else {
            return $translated;
        }
    }

    /**
     * @param $domain
     *
     * @return string
     */
    protected function getBackendForDomain($domain)
    {
        $backends = [
            'messages' => 'website',
            'admin' => 'admin'
        ];

        if (isset($backends[$domain])) {
            return $backends[$domain];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getAdminPath()
    {
        return $this->adminPath;
    }

    /**
     * @param string $adminPath
     */
    public function setAdminPath($adminPath)
    {
        $this->adminPath = $adminPath;
    }

    /**
     * @return Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * @param Kernel $kernel
     */
    public function setKernel($kernel)
    {
        $this->kernel = $kernel;
    }

    public function getDisableTranslations(): bool
    {
        return $this->disableTranslations;
    }

    public function setDisableTranslations(bool $disableTranslations)
    {
        $this->disableTranslations = $disableTranslations;
    }

    /**
     * Passes through all unknown calls onto the translator object.
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->translator, $method], $args);
    }
}
