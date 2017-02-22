<?php

namespace Pimcore\Bundle\PimcoreBundle\Component\Translation;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Pimcore\Cache;
use Pimcore\Tool; 

class Translator implements TranslatorInterface, TranslatorBagInterface {

    /**
     * @var TranslatorInterface|TranslatorBagInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $initializedCatalogues= [];

    /**
     * @var MessageSelector
     */
    private $selector;

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
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        $catalogue = $this->getCatalogue($locale);
        $locale = $catalogue->getLocale();
        $this->lazyInitialize($domain, $locale);

        $term = $catalogue->get((string) $id, $domain);
        $term = $this->checkForEmptyTranslation($id, $term, $domain, $locale);
        return strtr($term, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        $parameters = array_merge(array(
            '%count%' => $number,
        ), $parameters);

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
    protected function lazyInitialize($domain, $locale) {

        $cacheKey = "translation_data_" . $domain . "_" . $locale;

        if(isset($this->initializedCatalogues[$cacheKey])) {
            return;
        }

        $this->initializedCatalogues[$cacheKey] = true;
        $backend = $this->getBackendForDomain($domain);

        if($backend) {
            $catalogue = null;

            if (!$catalogue = Cache::load($cacheKey)) {
                $data = ["__pimcore_dummy" => "only_a_dummy"];
                $listClass = "\\Pimcore\\Model\\Translation\\" . ucfirst($backend) . "\\Listing";
                $list = new $listClass();

                $list->setCondition("language = ?", [$locale]);
                $translations = $list->loadRaw();

                foreach ($translations as $translation) {
                    $data[$translation["key"]] = Tool\Text::removeLineBreaks($translation["text"]);
                }

                $data = [$domain => $data];
                $catalogue = new MessageCatalogue($locale, $data);

                Cache::save($catalogue, $cacheKey, ["translator", "translator_website", "translate"], null, 999);
            }

            if ($catalogue) {
                $this->getCatalogue()->addCatalogue($catalogue);
            }
        }
    }

    /**
     * @param string $id
     * @param string $translated
     * @param string $domain
     * @param string $locale
     * @return string
     * @throws \Exception
     */
    protected function checkForEmptyTranslation($id, $translated, $domain, $locale)
    {
        if($id != $translated && $translated) {
            return $translated;
        } else if($id == $translated && !$this->getCatalogue($locale)->has($id, $domain)) {

            $backend = $this->getBackendForDomain($domain);
            if($backend) {
                if (strlen($id) > 190) {
                    throw new \Exception("Pimcore_Translate: Message ID's longer than 190 characters are invalid!");
                }

                $class = "\\Pimcore\\Model\\Translation\\" . ucfirst($backend);

                // no translation found create key
                if (Tool::isValidLanguage($locale)) {
                    try {
                        $t = $class::getByKey($id);
                        $t->addTranslation($locale, "");
                    } catch (\Exception $e) {
                        $t = new $class();
                        $t->setKey($id);

                        // add all available languages
                        $availableLanguages = (array)Tool::getValidLanguages();
                        foreach ($availableLanguages as $language) {
                            $t->addTranslation($language, "");
                        }
                    }

                    $t->save();
                }

                // put it into the catalogue, otherwise when there are more calls to the same key during one process
                // the key would be inserted/updated several times, what would be redundant
                $this->getCatalogue($locale)->set($id, $id, $domain);

                $translated = "";
            }
        }

        // now check for custom fallback locales, only for shared translations
        if(empty($translated) && $domain == "messages") {
            foreach (Tool::getFallbackLanguagesFor($locale) as $fallbackLanguage) {
                $this->lazyInitialize($domain, $fallbackLanguage);
                $catalogue = $this->getCatalogue($fallbackLanguage);
                if($catalogue->has($id, $domain)) {
                    return $catalogue->get($id, $domain);
                }
            }

            return $id;
        }

        return $translated;
    }

    /**
     * @param $domain
     * @return string
     */
    protected function getBackendForDomain($domain) {
        $backends = [
            "messages" => "website",
            "admin" => "admin"
        ];

        if(isset($backends[$domain])) {
            return $backends[$domain];
        }

        return false;
    }

    /**
     * Passes through all unknown calls onto the translator object.
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->translator, $method), $args);
    }
}
