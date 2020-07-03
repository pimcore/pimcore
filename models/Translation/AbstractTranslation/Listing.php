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
 * @method Model\Translation\AbstractTranslation[] load()
 * @method Model\Translation\AbstractTranslation current()
 * @method int getTotalCount()
 * @method void onCreateQuery(callable $callback)
 */
class Listing extends Model\Listing\AbstractListing
{
    /** @var int maximum number of cacheable items */
    protected static $cacheLimit = 5000;

    /**
     * @var array|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $translations = null;

    public function __construct()
    {
        $this->translations = & $this->data;
    }

    /**
     * @return \Pimcore\Model\Translation\AbstractTranslation[]
     */
    public function getTranslations()
    {
        return $this->getData();
    }

    /**
     * @param array $translations
     *
     * @return static
     */
    public function setTranslations($translations)
    {
        return $this->setData($translations);
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
