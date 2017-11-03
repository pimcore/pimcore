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

namespace Pimcore\Targeting\DataProvider;

use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Request;

interface DataProviderInterface
{
    /**
     * The provider key used to identify a provider. This key will
     * be used from conditions to specify which providers to use.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Loads data from the current request into the visitor info
     *
     * @param Request $request
     * @param VisitorInfo $visitorInfo
     */
    public function load(Request $request, VisitorInfo $visitorInfo);
}
