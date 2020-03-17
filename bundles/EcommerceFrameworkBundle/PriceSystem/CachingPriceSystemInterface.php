<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

interface CachingPriceSystemInterface extends PriceSystemInterface
{
    /**
     * Loads price infos once for given product entries and caches them
     *
     * @param array $productEntries
     * @param array $options
     *
     * @return mixed
     */
    public function loadPriceInfos($productEntries, $options);

    /**
     * Clears cached price infos
     *
     * @param array $productEntries
     * @param array $options
     *
     * @return mixed
     */
    public function clearPriceInfos($productEntries, $options);
}

class_alias(CachingPriceSystemInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ICachingPriceSystem');
