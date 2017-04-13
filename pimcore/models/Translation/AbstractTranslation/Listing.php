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
 * @category   Pimcore
 * @package    Translation
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation\AbstractTranslation;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Translation\AbstractTranslation\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{
    /** @var int maximum number of cacheable items */
    protected static $cacheLimit = 5000;

    /**
     * Contains the results of the list. They are all an instance of Staticroute
     *
     * @var array
     */
    public $translations = [];

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     *
     * @param $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param array $translations
     *
     * @return $this
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * @return int
     */
    public static function getCacheLimit()
    {
        return self::$cacheLimit;
    }

    /**
     * @param int $cacheLimit
     */
    public static function setCacheLimit($cacheLimit)
    {
        self::$cacheLimit = $cacheLimit;
    }
}
