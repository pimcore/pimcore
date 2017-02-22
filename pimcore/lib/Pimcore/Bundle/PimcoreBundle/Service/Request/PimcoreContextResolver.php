<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Pimcore\Bundle\PimcoreBundle\Service\Context\PimcoreContextGuesser;
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
     * @return string|null
     */
    public function getPimcoreContext(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(static::ATTRIBUTE_PIMCORE_CONTEXT);
    }

    /**
     * Set the pimcore context on the request
     *
     * @param Request $request
     * @param string $context
     */
    public function setPimcoreContext(Request $request, $context)
    {
        $request->attributes->set(static::ATTRIBUTE_PIMCORE_CONTEXT, $context);
    }

    /**
     * Guess the pimcore context
     *
     * @param Request $request
     * @return string
     */
    public function guessPimcoreContext(Request $request)
    {
        return $this->guesser->guess($request);
    }
}
