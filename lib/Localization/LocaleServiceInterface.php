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

namespace Pimcore\Localization;

interface LocaleServiceInterface
{
    public function isLocale(string $locale): bool;

    public function findLocale(): string;

    public function getLocaleList(): array;

    public function getDisplayRegions(?string $locale = null): array;

    public function getLocale(): ?string;

    public function setLocale(?string $locale): void;

    public function hasLocale(): bool;
}
