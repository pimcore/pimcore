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

namespace Pimcore\Http\Request\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
abstract class AbstractRequestResolver
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return Request
     */
    protected function getCurrentRequest()
    {
        if (!$this->requestStack->getCurrentRequest()) {
            throw new \LogicException('A request must be available.');
        }

        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @return Request
     */
    protected function getMasterRequest()
    {
        return $this->getMainRequest();
    }

    /**
     * @return Request
     */
    protected function getMainRequest(): Request
    {
        if (!$this->requestStack->getMainRequest()) {
            throw new \LogicException('A main request must be available.');
        }

        return $this->requestStack->getMainRequest();
    }
}
