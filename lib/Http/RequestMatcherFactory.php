<?php
declare(strict_types=1);

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

namespace Pimcore\Http;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;

/**
 * @internal
 */
class RequestMatcherFactory
{
    /**
     * Builds a set of request matchers from a config definition as configured in pimcore.admin.routes (see PimcoreCoreBundle
     * configuration).
     *
     * @param array $entries
     *
     * @return ChainRequestMatcher
     */
    public function buildRequestMatchers(array $entries): ChainRequestMatcher
    {

        return new ChainRequestMatcher($entries);
    }
}
