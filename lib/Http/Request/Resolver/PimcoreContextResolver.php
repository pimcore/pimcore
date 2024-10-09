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

namespace Pimcore\Http\Request\Resolver;

use InvalidArgumentException;
use Pimcore\Http\Context\PimcoreContextGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gets/sets and guesses pimcore context (admin, default) from request. The guessing is implemented in PimcoreContextGuesser
 * and matches the request against a list of paths and routes which are exposed via config.
 */
class PimcoreContextResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_PIMCORE_CONTEXT = '_pimcore_context';

    const CONTEXT_ADMIN = 'admin';

    const CONTEXT_DEFAULT = 'default';

    protected PimcoreContextGuesser $guesser;

    public function __construct(RequestStack $requestStack, PimcoreContextGuesser $guesser)
    {
        $this->guesser = $guesser;

        parent::__construct($requestStack);
    }

    /**
     * Get pimcore context from request
     *
     *
     */
    public function getPimcoreContext(Request $request = null): ?string
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $context = $request->attributes->get(self::ATTRIBUTE_PIMCORE_CONTEXT);

        if (!$context) {
            $context = $this->guesser->guess($request, static::CONTEXT_DEFAULT);
            $this->setPimcoreContext($request, $context);
        }

        return $context;
    }

    /**
     * Sets the pimcore context on the request
     *
     */
    public function setPimcoreContext(Request $request, string $context): void
    {
        $request->attributes->set(self::ATTRIBUTE_PIMCORE_CONTEXT, $context);
    }

    /**
     * Tests if the request matches a given contect. $context can also be an array of contexts. If one
     * of the contexts matches, the method will return true.
     *
     *
     */
    public function matchesPimcoreContext(Request $request, array|string $context): bool
    {
        if (!is_array($context)) {
            if (!empty($context)) {
                $context = [$context];
            } else {
                $context = [];
            }
        }

        if (empty($context)) {
            throw new InvalidArgumentException('Can\'t match against empty pimcore context');
        }

        $resolvedContext = $this->getPimcoreContext($request);
        if (!$resolvedContext) {
            // no context available to match -> false
            return false;
        }

        foreach ($context as $ctx) {
            if ($ctx === $resolvedContext) {
                return true;
            }
        }

        return false;
    }
}
