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

    public function markLazyKeyAsLoaded(string $key)
    {
        $this->loadedLazyKeys[$key] = 1;
    }

    public function unmarkLazyKeyAsLoaded(string $key)
    {
        unset($this->loadedLazyKeys[$key]);
    }

    public function isLazyKeyLoaded(string $key): bool
    {
        if ($this->isAllLazyKeysMarkedAsLoaded()) {
            return true;
        }

        $isset = isset($this->loadedLazyKeys[$key]);

        return $isset;
    }

    public function buildLazyKey(string $name, string $language): string
    {
        return $name . LazyLoadedFieldsInterface::LAZY_KEY_SEPARATOR . $language;
    }
}
