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

namespace Pimcore\Localization;

interface LocaleServiceInterface
{
    /**
     * @param string $locale
     *
     * @return bool
     */
    public function isLocale($locale);

    /**
     * @return string
     */
    public function findLocale();

    /**
     * @return array
     */
    public function getLocaleList();

    /**
     * @param string|null $locale
     *
     * @return array
     */
    public function getDisplayRegions($locale = null);

    /**
     * @return string|null
     */
    public function getLocale();

    /**
     * @param string|null $locale
     */
    public function setLocale($locale);

    /**
     * @return bool
     */
    public function hasLocale();
}
