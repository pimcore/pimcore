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

namespace Pimcore\Model\DataObject;

interface LazyLoadedFieldsInterface
{
    const LAZY_KEY_SEPARATOR = '~~';

    public function markLazyKeyAsLoaded(string $key): void;

    public function isLazyKeyLoaded(string $key): bool;

    /**
     * @internal
     *
     */
    public function isAllLazyKeysMarkedAsLoaded(): bool;
}
