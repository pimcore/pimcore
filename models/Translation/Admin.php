<?php

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

namespace Pimcore\Model\Translation;

use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Translation\Admin\Dao getDao()
 *
 * @deprecated use \Pimcore\Model\Translation with domain "admin" instead
 */
class Admin extends AbstractTranslation
{
    /**
     * @return array
     */
    public static function getLanguages(): array
    {
        return \Pimcore\Tool\Admin::getLanguages();
    }

    /**
     * @param string $id
     * @param bool $create
     * @param bool $returnIdIfEmpty
     * @param string|null $language
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getByKeyLocalized($id, $create = false, $returnIdIfEmpty = false, $language = null)
    {
        $language = null;

        if ($user = Tool\Admin::getCurrentUser()) {
            $language = $user->getLanguage();
        } elseif ($user = Tool\Authentication::authenticateSession()) {
            $language = $user->getLanguage();
        }

        if (!$language) {
            $language = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();
        }

        if (!in_array($language, Tool\Admin::getLanguages())) {
            $config = \Pimcore\Config::getSystemConfiguration('general');
            $language = $config['language'] ?? null;
        }

        return parent::getByKeyLocalized($id, $create, $returnIdIfEmpty, $language);
    }

    /**
     * @inheritDoc
     */
    public static function importTranslationsFromFile($file /*, $domain = self::DOMAIN_DEFAULT, $replaceExistingTranslations = true, $languages = null, $dialect = null */)
    {
        $args = func_get_args();

        //old params set
        if (isset($args[1]) && is_bool($args[1])) {
            $domain = self::DOMAIN_ADMIN;
            $replaceExistingTranslations = $args[1];
            $languages = $args[2] ?? null;
            $dialect = $args[3] ?? null;
        } else {
            $domain = $args[1] ?? self::DOMAIN_ADMIN;
            $replaceExistingTranslations = $args[2] ?? true;
            $languages = $args[3] ?? null;
            $dialect = $args[4] ?? null;
        }

        return parent::importTranslationsFromFile($file, $domain, $replaceExistingTranslations, $languages, $dialect);
    }
}
