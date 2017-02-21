<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gets/sets and guesses request context (admin, default) from request. The guessing is implemented in RequestContextGuesser
 * and matches the request against a list of paths and routes which are exposed via config.
 */
class RequestContextResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_REQUEST_CONTEXT = '_request_context';

    const REQUEST_CONTEXT_ADMIN = 'admin';
    const REQUEST_CONTEXT_DEFAULT = 'default';

    /**
     * @var RequestContextGuesser
     */
    protected $guesser;

    /**
     * @inheritDoc
     */
    public function __construct(RequestStack $requestStack, RequestContextGuesser $guesser)
    {
        $this->guesser = $guesser;

        parent::__construct($requestStack);
    }

    /**
     * Get request context from request
     *
     * @param Request|null $request
     * @return string|null
     */
    public function getRequestContext(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(static::ATTRIBUTE_REQUEST_CONTEXT);
    }

    /**
     * Set the request context on the request
     *
     * @param Request $request
     * @param string $context
     */
    public function setRequestContext(Request $request, $context)
    {
        $request->attributes->set(static::ATTRIBUTE_REQUEST_CONTEXT, $context);
    }

    /**
     * Guess the request context
     *
     * @param Request $request
     * @return string
     */
    public function guessRequestContext(Request $request)
    {
        return $this->guesser->guess($request);
    }
}
