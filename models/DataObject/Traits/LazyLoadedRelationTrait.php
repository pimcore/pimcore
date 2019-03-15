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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

trait LazyLoadedRelationTrait
{
    /**
     * @var bool
     */
    protected static $disableLazyLoading = false;

    /**
     * @var array
     */
    protected $lazyKeys = [];

    /**
     * @param $key
     */
    public function addLazyKey($key)
    {
        $this->lazyKeys[$key] = 1;
    }

    /**
     * @param $key
     */
    public function removeLazyKey($key)
    {
        unset($this->lazyKeys[$key]);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasLazyKey($key)
    {
        $isset = isset($this->lazyKeys[$key]);

        return $isset;
    }

    /**
     * @return bool
     */
    public function hasLazyKeys()
    {
        return count($this->lazyKeys) > 0;
    }

    /**
     * @return array
     */
    public function getLazyKeys()
    {
        return $this->lazyKeys;
    }

    /**
     * @return bool
     */
    public static function isLazyLoadingDisabled()
    {
        return self::$disableLazyLoading;
    }

    /**
     * @internal
     *
     * @param bool $disableLazyLoading
     */
    public static function setDisableLazyLoading(bool $disableLazyLoading)
    {
        self::$disableLazyLoading = $disableLazyLoading;
    }

    /**
     * @internal
     * Disables lazy loading
     */
    public static function disableLazyLoading()
    {
        self::setDisableLazyloading(true);
    }

    /**
     * @internal
     * Enables the lazy loading
     */
    public static function enableLazyloading()
    {
        self::setDisableLazyloading(false);
    }
}
