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

namespace Pimcore\Bundle\PimcoreBundle\Context\Initializer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

interface ContextInitializerInterface
{
    /**
     * Determines if the initializer should be called for the given context
     *
     * @param string $context
     * @return bool
     */
    public function supports($context);

    /**
     * Initializes system for the given context
     *
     * @param Request $request
     * @param string $context
     * @param int $requestType
     */
    public function initialize(Request $request, $context, $requestType = KernelInterface::MASTER_REQUEST);
}
