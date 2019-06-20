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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService;

interface FilterServiceLocatorInterface
{
    public function getFilterService(string $tenant = null): FilterService;

    public function hasFilterService(string $tenant): bool;
}

class_alias(FilterServiceLocatorInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\IFilterServiceLocator');
