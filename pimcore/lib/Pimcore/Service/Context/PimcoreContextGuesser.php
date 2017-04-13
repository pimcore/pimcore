<?php
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

namespace Pimcore\Service\Context;

use Pimcore\Service\RequestMatcherFactory;

class PimcoreContextGuesser
{
    /**
     * @param RequestMatcherFactory $factory
     */
    public function __construct(RequestMatcherFactory $factory)
    {
        $this->requestMatcherFactory = $factory;
    }
}
