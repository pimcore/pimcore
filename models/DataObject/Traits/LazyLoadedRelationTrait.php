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
    protected $loadedLazyKeys = [];

    /**
     * @var bool
     */
    protected $allLazyKeysMarkedAsLoaded = false;

    /**
     * @param string $key
     */
    public function markLazyKeyAsLoaded(string $key)
    {
        $this->loadedLazyKeys[$key] = 1;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isLazyKeyLoaded(string $key) : bool
    {
        if($this->isAllLazyKeysMarkedAsLoaded()) {
            return true;
        }

        $isset = isset($this->loadedLazyKeys[$key]);
        return $isset;
    }

    /**
     * @return bool
     */
    protected function isAllLazyKeysMarkedAsLoaded() : bool {
        return $this->allLazyKeysMarkedAsLoaded;
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
