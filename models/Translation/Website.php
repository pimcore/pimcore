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

/**
 * @method \Pimcore\Model\Translation\Website\Dao getDao()
 *
 * @deprecated use \Pimcore\Model\Translation with domain "messages" instead
 */
class Website extends AbstractTranslation
{
    /**
     * @return array
     */
    public static function getLanguages(): array
    {
        return \Pimcore\Tool::getValidLanguages();
    }

    /**
     * @inheritDoc
     */
    public static function importTranslationsFromFile($file /*, $domain = self::DOMAIN_DEFAULT, $replaceExistingTranslations = true, $languages = null, $dialect = null */)
    {
        $args = func_get_args();

        //old params set
        if (isset($args[1]) && is_bool($args[1])) {
            $domain = self::DOMAIN_DEFAULT;
            $replaceExistingTranslations = $args[1] ?? true;
            $languages = $args[2] ?? null;
            $dialect = $args[3] ?? null;
        } else {
            $domain = $args[1] ?? self::DOMAIN_DEFAULT;
            $replaceExistingTranslations = $args[2] ?? true;
            $languages = $args[3] ?? null;
            $dialect = $args[4] ?? null;
        }

        return parent::importTranslationsFromFile($file, $domain, $replaceExistingTranslations, $languages, $dialect);
    }
}
