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

use Pimcore\Model\Translation;

/**
 * @method \Pimcore\Model\Translation\AbstractTranslation\Dao getDao()
 *
 * @deprecated use \Pimcore\Model\Translation with domain instead
 */
abstract class AbstractTranslation extends Translation
{
    /**
     * @deprecated
     *
     * @param array $data
     */
    public function getFromWebserviceImport($data)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            $this->$setter($value);
        }
    }

    /**
     * @deprecated
     *
     * @return array
     */
    public function getForWebserviceExport()
    {
        $data = get_object_vars($this);
        unset($data['dao']);

        return $data;
    }

    /**
     * @param string $id
     * @param bool $create
     * @param bool $returnIdIfEmpty
     *
     * @return static|null
     */
    public static function getByKey($id, $create = false, $returnIdIfEmpty = false)
    {
        $cacheKey = 'translation_' . $id;
        if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            return \Pimcore\Cache\Runtime::get($cacheKey);
        }

        $domain = 'messages';
        if (static::class === Admin::class) {
            $domain = 'admin';
        }

        return parent::getByKey($id, $domain, $create, $returnIdIfEmpty);
    }

    /**
     * Static Helper to get the translation of the current locale
     *
     * @static
     *
     * @param string $id - translation key
     * @param bool $create - creates an empty translation entry if the key doesn't exists
     * @param bool $returnIdIfEmpty - returns $id if no translation is available
     * @param string $language
     *
     * @return string|null
     */
    public static function getByKeyLocalized($id, $create = false, $returnIdIfEmpty = false, $language = null)
    {
        if (!$language) {
            $language = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();
            if (!$language) {
                return null;
            }
        }

        $translationItem = self::getByKey($id, $create, $returnIdIfEmpty);
        if ($translationItem instanceof self) {
            return $translationItem->getTranslation($language);
        }

        return null;
    }
}
