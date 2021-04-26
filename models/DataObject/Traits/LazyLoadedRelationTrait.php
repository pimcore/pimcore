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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\LazyLoadedFieldsInterface;

/**
 * @internal
 */
trait LazyLoadedRelationTrait
{
    /**
     * @var array
     */
    protected $loadedLazyKeys = [];

    /**
     * @param string $key
     */
    public function markLazyKeyAsLoaded(string $key)
    {
        $this->loadedLazyKeys[$key] = 1;
    }

    /**
     * @param string $key
     */
    public function unmarkLazyKeyAsLoaded(string $key)
    {
        unset($this->loadedLazyKeys[$key]);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isLazyKeyLoaded(string $key): bool
    {
        if ($this->isAllLazyKeysMarkedAsLoaded()) {
            return true;
        }

        $isset = isset($this->loadedLazyKeys[$key]);

        return $isset;
    }

    /**
     * @param string $name
     * @param string $language
     *
     * @return string
     */
    public function buildLazyKey(string $name, string $language): string
    {
        return $name . LazyLoadedFieldsInterface::LAZY_KEY_SEPARATOR . $language;
    }
}
