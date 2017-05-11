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

namespace Pimcore\Service\Request;

use Pimcore\Service\Context\PimcoreContextGuesser;
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
    const CONTEXT_WEBSERVICE = 'webservice';
    const CONTEXT_DEFAULT = 'default';

    /**
     * @var PimcoreContextGuesser
     */
    protected $guesser;

    /**
     * @inheritDoc
     */
    public function __construct(RequestStack $requestStack, PimcoreContextGuesser $guesser)
    {
        $this->guesser = $guesser;

        parent::__construct($requestStack);
    }

    /**
     * Get pimcore context from request
     *
     * @param Request|null $request
     *
     * @return string|null
     */
    public function getPimcoreContext(Request $request = null)
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
     * @param Request $request
     * @param string $context
     */
    public function setPimcoreContext(Request $request, string $context)
    {
        $request->attributes->set(self::ATTRIBUTE_PIMCORE_CONTEXT, $context);
    }
}
