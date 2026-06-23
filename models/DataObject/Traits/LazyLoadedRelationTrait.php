<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\LazyLoadedFieldsInterface;

/**
 * @internal
 */
trait LazyLoadedRelationTrait
{
    protected array $loadedLazyKeys = [];

    public function markLazyKeyAsLoaded(string $key): void
    {
        $this->loadedLazyKeys[$key] = 1;
    }

    public function unmarkLazyKeyAsLoaded(string $key): void
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
