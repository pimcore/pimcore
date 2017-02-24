<?php

namespace Pimcore\Facade;

use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

class Translate
{
    const DOMAIN_ADMIN = 'admin';

    /**
     * @return TranslatorInterface
     */
    public static function getTranslator()
    {
        return \Pimcore::getContainer()->get('translator');
    }

    /**
     * Translates the given message.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public static function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return static::getTranslator()->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param int         $number     The number to use to find the indice of the message
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return static::getTranslator()->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * Translates the given message in the admin domain.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public static function transAdmin($id, array $parameters = [], $locale = null)
    {
        return static::trans($id, $parameters, static::DOMAIN_ADMIN, $locale);
    }

    /**
     * Translates the given choice message by choosing a translation according to a number in the admin domain.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param int         $number     The number to use to find the indice of the message
     * @param array       $parameters An array of parameters for the message
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public static function transChoiceAdmin($id, $number, array $parameters = [], $locale = null)
    {
        return static::transChoice($id, $number, $parameters, static::DOMAIN_ADMIN, $locale);
    }
}
