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
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
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
     * @var IdentityTranslator
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
     * @param TranslatorInterface $translator
     * @param bool $caseInsensitive
     */
    public function __construct(TranslatorInterface $translator, bool $caseInsensitive = false)
    {
        if (!$translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException(sprintf('The Translator "%s" must implement TranslatorInterface and TranslatorBagInterface.', get_class($translator)));
        }

        $this->translator = $translator;
        $this->selector = new IdentityTranslator();

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

        $id = (string) $id;
        $catalogue = $this->getCatalogue($locale);
        $locale = $catalogue->getLocale();

        $this->lazyInitialize($domain, $locale);

        if (isset($parameters['%count%'])) {
            $number = (float)$parameters['%count%'];

            $parts = [];
            if (preg_match('/^\|++$/', $id)) {
                $parts = explode('|', $id);
            } elseif (preg_match_all('/(?:\|\||[^\|])++/', $id, $matches)) {
                $parts = $matches[0];
            }

            $intervalRegexp = <<<'EOF'
/^(?P<interval>
    ({\s*
        (\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)
    \s*})

        |

    (?P<left_delimiter>[\[\]])
        \s*
        (?P<left>-Inf|\-?\d+(\.\d+)?)
        \s*,\s*
        (?P<right>\+?Inf|\-?\d+(\.\d+)?)
        \s*
    (?P<right_delimiter>[\[\]])
)\s*(?P<message>.*?)$/xs
EOF;

            $standardRules = [];
            foreach ($parts as $part) {
                $part = trim(str_replace('||', '|', $part));

                // try to match an explicit rule, then fallback to the standard ones
                if (preg_match($intervalRegexp, $part, $matches)) {
                    if ($matches[2]) {
                        foreach (explode(',', $matches[3]) as $n) {
                            if ($number == $n) {
                                return strtr($matches['message'], $parameters);
                            }
                        }
                    } else {
                        $leftNumber = '-Inf' === $matches['left'] ? -INF : (float)$matches['left'];
                        $rightNumber = is_numeric($matches['right']) ? (float)$matches['right'] : INF;

                        if (('[' === $matches['left_delimiter'] ? $number >= $leftNumber : $number > $leftNumber)
                            && (']' === $matches['right_delimiter'] ? $number <= $rightNumber : $number < $rightNumber)
                        ) {
                            return strtr($matches['message'], $parameters);
                        }
                    }
                } elseif (preg_match('/^\w+\:\s*(.*?)$/', $part, $matches)) {
                    $standardRules[] = $matches[1];
                } else {
                    $standardRules[] = $part;
                }
            }

            $position = $this->getPluralizationRule($number, $locale);
            if (!isset($standardRules[$position])) {
                // when there's exactly one rule given, and that rule is a standard
                // rule, use this rule
                if (1 === \count($parts) && isset($standardRules[0])) {
                    return strtr($standardRules[0], $parameters);
                }

                $message = sprintf('Unable to choose a translation for "%s" with locale "%s" for value "%d". Double check that this translation has the correct plural options (e.g. "There is one apple|There are %%count%% apples").', $id, $locale, $number);

                if (class_exists(InvalidArgumentException::class)) {
                    throw new InvalidArgumentException($message);
                }

                throw new \InvalidArgumentException($message);
            }

            $id = strtr($standardRules[$position], $parameters);
        }

        $term = $this->getFromCatalogue($catalogue, $id, $domain, $locale);
        $term = strtr($term, $parameters);

        // check for an indexed array, that used the ZF1 vsprintf() notation for parameters
        if (isset($parameters[0])) {
            $term = vsprintf($term, $parameters);
        }

        $term = $this->updateLinks($term);

        return $term;
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        @trigger_error(
            'transChoice is deprecated since version 6.0.1 and will be removed in 7.0.0. ' .
            ' Use the trans() method with "%count%" parameter.',
            E_USER_DEPRECATED
        );

        return $this->trans($id, ['%count%' => $number] + $parameters, $domain, $locale);
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
                            $defaultCatalog = $this->getCatalogue($locale);

                            foreach ($jsonTranslations as $translationKey => $translationValue) {
                                if (!$defaultCatalog->has($translationKey, 'admin')) {
                                    $data[$translationKey] = $translationValue;
                                }
                            }
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
                if ($domain == 'admin') {
                    $aliasesPath = $this->getKernel()->locateResource($this->getAdminPath() . '/aliases.json');
                    $aliases = json_decode(file_get_contents($aliasesPath), true);
                    foreach ($aliases as $aliasTarget => $aliasSource) {
                        if (isset($data[$aliasSource]) && (!isset($data[$aliasTarget]) || empty($data[$aliasTarget]))) {
                            $data[$aliasTarget] = $data[$aliasSource];
                        }
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

                    /**
                     * @var AbstractTranslation $t
                     */
                    $t = $class::getByKey($id);
                    if ($t) {
                        if (!$t->hasTranslation($locale)) {
                            $t->addTranslation($locale, '');
                        } else {
                            // return the original not lowercased ID
                            return $id;
                        }
                    } else {
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

    public function updateLinks(string $text)
    {
        if (strpos($text, 'pimcore_id')) {
            $text = Tool\Text::wysiwygText($text);
        }

        return $text;
    }

    public function getCaseInsensitive(): bool
    {
        return $this->caseInsensitive;
    }

    /**
     * Passes through all unknown calls onto the translator object.
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->translator, $method], $args);
    }

    /**
     * Returns the plural rules for a given locale. used in Symfony\Contracts\Translation\TranslatorTrait
     *
     * @param int $number
     * @param string $locale
     * @return int
     */
    private function getPluralizationRule(int $number, string $locale): int
    {
        switch ('pt_BR' !== $locale && \strlen($locale) > 3 ? substr($locale, 0, strrpos($locale, '_')) : $locale) {
            case 'af':
            case 'bn':
            case 'bg':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hu':
            case 'is':
            case 'it':
            case 'ku':
            case 'lb':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'nn':
            case 'no':
            case 'oc':
            case 'om':
            case 'or':
            case 'pa':
            case 'pap':
            case 'ps':
            case 'pt':
            case 'so':
            case 'sq':
            case 'sv':
            case 'sw':
            case 'ta':
            case 'te':
            case 'tk':
            case 'ur':
            case 'zu':
                return (1 == $number) ? 0 : 1;

            case 'am':
            case 'bh':
            case 'fil':
            case 'fr':
            case 'gun':
            case 'hi':
            case 'hy':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'pt_BR':
            case 'ti':
            case 'wa':
                return ((0 == $number) || (1 == $number)) ? 0 : 1;

            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sh':
            case 'sr':
            case 'uk':
                return ((1 == $number % 10) && (11 != $number % 100)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);

            case 'cs':
            case 'sk':
                return (1 == $number) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);

            case 'ga':
                return (1 == $number) ? 0 : ((2 == $number) ? 1 : 2);

            case 'lt':
                return ((1 == $number % 10) && (11 != $number % 100)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);

            case 'sl':
                return (1 == $number % 100) ? 0 : ((2 == $number % 100) ? 1 : (((3 == $number % 100) || (4 == $number % 100)) ? 2 : 3));

            case 'mk':
                return (1 == $number % 10) ? 0 : 1;

            case 'mt':
                return (1 == $number) ? 0 : (((0 == $number) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3));

            case 'lv':
                return (0 == $number) ? 0 : (((1 == $number % 10) && (11 != $number % 100)) ? 1 : 2);

            case 'pl':
                return (1 == $number) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);

            case 'cy':
                return (1 == $number) ? 0 : ((2 == $number) ? 1 : (((8 == $number) || (11 == $number)) ? 2 : 3));

            case 'ro':
                return (1 == $number) ? 0 : (((0 == $number) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);

            case 'ar':
                return (0 == $number) ? 0 : ((1 == $number) ? 1 : ((2 == $number) ? 2 : ((($number % 100 >= 3) && ($number % 100 <= 10)) ? 3 : ((($number % 100 >= 11) && ($number % 100 <= 99)) ? 4 : 5))));

            default:
                return 0;
        }
    }

}
