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

declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\RateLimiter;

use Pimcore\Bundle\AdminBundle\Security\Authenticator\AdminSessionAuthenticator;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Policy\NoLimiter;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * @internal
 * Disables rate limit on the \Pimcore\Bundle\AdminBundle\Security\Authenticator\AdminSessionAuthenticator
 */
class AdminRequestRateLimiter implements RequestRateLimiterInterface
{
    public function __construct(
        private RequestRateLimiterInterface $decorated,
    ) {}

    public function consume(Request $request): RateLimit
    {
        if ($request->attributes->get(AdminSessionAuthenticator::REQUEST_ATTRIBUTE_SESSION_AUTHENTICATED)) {
            return (new NoLimiter())->consume();
        }

        return $this->decorated->consume($request);
    }

    public function reset(Request $request): void
    {
        if ($request->attributes->get(AdminSessionAuthenticator::REQUEST_ATTRIBUTE_SESSION_AUTHENTICATED)) {
            return;
        }

        $this->decorated->reset($request);
    }
}
