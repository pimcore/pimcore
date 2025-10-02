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

namespace Pimcore\Http\Request\Resolver;

use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
abstract class AbstractRequestResolver
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    protected function getCurrentRequest(): Request
    {
        if (!$this->requestStack->getCurrentRequest()) {
            throw new LogicException('A request must be available.');
        }

        return $this->requestStack->getCurrentRequest();
    }

    protected function getMainRequest(): Request
    {
        if (!$this->requestStack->getMainRequest()) {
            throw new LogicException('A main request must be available.');
        }

        return $this->requestStack->getMainRequest();
    }
}
