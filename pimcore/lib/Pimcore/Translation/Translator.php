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
use Pimcore\Tool;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogue;
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
     * @var Kernel
     */
    protected $kernel;

    /**
     * @param TranslatorInterface $translator The translator must implement TranslatorBagInterface
     */
    public function __construct(TranslatorInterface $translator)
    {
        if (!$translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException(sprintf('The Translator "%s" must implement TranslatorInterface and TranslatorBagInterface.', get_class($translator)));
        }

        $this->translator = $translator;
        $this->selector = new MessageSelector();
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        $catalogue = $this->getCatalogue($locale);
        $locale = $catalogue->getLocale();
        $this->lazyInitialize($domain, $locale);

        $term = $catalogue->get((string) $id, $domain);
        $term = $this->checkForEmptyTranslation($id, $term, $domain, $locale);
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

        $term = $catalogue->get($id, $domain);
        $term = $this->checkForEmptyTranslation($id, $term, $domain, $locale);
        $term = $this->selector->choose($term, (int) $number, $locale);

        return strtr($term, $parameters);
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
                    // add json catalogue
                    try {
                        $jsonPath = $this->getKernel()->locateResource($this->getAdminPath() . '/' . $locale . '.json');
                    } catch (\Exception $e) {
                        $jsonPath = $this->getKernel()->locateResource($this->getAdminPath() . '/en.json');
                    }

                    $jsonTranslations = json_decode(file_get_contents($jsonPath), true);
                    if (is_array($jsonTranslations)) {
                        $data = array_merge($data, $jsonTranslations);
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
                        $data[$translation['key']] = $translationTerm;
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
        $lookForFallback = false;
        if (empty($id)) {
            return $translated;
        } elseif ($id != $translated && $translated) {
            return $translated;
        } elseif ($id == $translated && !$this->getCatalogue($locale)->has($id, $domain)) {
            $backend = $this->getBackendForDomain($domain);
            if ($backend) {
                if (strlen($id) > 190) {
                    throw new \Exception("Message ID's longer than 190 characters are invalid!");
                }

                $class = '\\Pimcore\\Model\\Translation\\' . ucfirst($backend);

                // no translation found create key
                if (Tool::isValidLanguage($locale)) {
                    try {
                        /**
                         * @var AbstractTranslation $t
                         */
                        $t = $class::getByKey($id);
                        if (!$t->hasTranslation($locale)) {
                            $t->addTranslation($locale, '');
                        } else {
                            return $translated;
                        }
                    } catch (\Exception $e) {
                        $t = new $class();
                        $t->setKey($id);

                        // add all available languages
                        $availableLanguages = (array)Tool::getValidLanguages();
                        foreach ($availableLanguages as $language) {
                            $t->addTranslation($language, '');
                        }
                    }

                    $t->save();
                }

                // put it into the catalogue, otherwise when there are more calls to the same key during one process
                // the key would be inserted/updated several times, what would be redundant
                $this->getCatalogue($locale)->set($id, $id, $domain);

                $lookForFallback = true;
            }
        }

        // now check for custom fallback locales, only for shared translations
        if ($lookForFallback && $domain == 'messages') {
            foreach (Tool::getFallbackLanguagesFor($locale) as $fallbackLanguage) {
                $this->lazyInitialize($domain, $fallbackLanguage);
                $catalogue = $this->getCatalogue($fallbackLanguage);
                if ($catalogue->has($id, $domain)) {
                    $fallbackValue = $catalogue->get($id, $domain);
                    if ($fallbackValue) {
                        return $fallbackValue;
                    }
                }
            }

            return $id;
        }

        return $translated;
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

    /**
     * Passes through all unknown calls onto the translator object.
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->translator, $method], $args);
    }
}
