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

namespace Pimcore\Model\Translation;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Translation\AbstractTranslation\Listing\Dao getDao()
 * @method Model\Translation\Translation[] load()
 * @method Model\Translation\Translation current()
 * @method int getTotalCount()
 * @method void onCreateQuery(callable $callback)
 *
 */
class Listing extends Model\Listing\AbstractListing
{
    /** @var int maximum number of cacheable items */
    protected static $cacheLimit = 5000;

    /**
     * @var string
     */
    protected $domain = Model\Translation\Translation::DOMAIN_DEFAULT;

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
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
     * @return \Pimcore\Model\Translation\Listing
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
