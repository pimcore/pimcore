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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Bundle\PimcoreBundle\Component\Translation\Translator;
use Pimcore\Cache;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

class Translate extends \Zend_Translate_Adapter
{
    /**
     * @var string
     */
    protected static $domain = "messages";

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param $locale
     */
    public function __construct($locale)
    {
        if (!$locale instanceof \Zend_Locale) {
            $locale = new \Zend_Locale($locale);
        }

        $locale = (string) $locale;

        parent::__construct([
            "locale" => $locale,
            "content" => []
        ]);

        $this->translator = \Pimcore::getContainer()->get("translator");
    }

    /**
     * @param null $data
     * @param $locale
     * @param array $options
     * @return array
     */
    protected function _loadTranslationData($data, $locale, array $options = [])
    {
        return [];
    }

    /**
     * @return string
     */
    public function toString()
    {
        // pseudo is needed by the interface but not by the application
        return "Array";
    }

    /**
     * @param array|string $messageId
     * @param null $locale
     * @return array|string
     * @throws \Exception
     */
    public function translate($messageId, $locale = null)
    {
        if(!$locale) {
            $locale = $this->getLocale();
        }

        $term = $this->translator->trans($messageId, [], static::$domain, $locale);
        return $term;
    }

    /**
     * @param string $messageId
     * @param bool $original
     * @param null $locale
     * @return bool
     */
    public function isTranslated($messageId, $original = false, $locale = null)
    {
        if(!$locale) {
            $locale = $this->getLocale();
        }

        return $this->translator->getCatalogue($locale)->has($messageId, static::$domain);
    }
}
