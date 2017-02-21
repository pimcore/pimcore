<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gets/sets and guesses request type (admin, default) from request. The guessing is implemented in RequestTypeGuesser and
 * matches the request against a list of paths and routes which are exposed via config.
 */
class RequestTypeResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_REQUEST_TYPE = '_request_type';

    const REQUEST_TYPE_ADMIN = 'admin';
    const REQUEST_TYPE_DEFAULT = 'default';

    /**
     * @var RequestTypeGuesser
     */
    protected $guesser;

    /**
     * @inheritDoc
     */
    public function __construct(RequestStack $requestStack, RequestTypeGuesser $guesser)
    {
        $this->guesser = $guesser;

        parent::__construct($requestStack);
    }

    /**
     * Get request type from request
     *
     * @param Request|null $request
     * @return string|null
     */
    public function getRequestType(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $request->attributes->get(static::ATTRIBUTE_REQUEST_TYPE);
    }

    /**
     * Set the request type on the request
     *
     * @param Request $request
     * @param string $requestType
     */
    public function setRequestType(Request $request, $requestType)
    {
        $request->attributes->set(static::ATTRIBUTE_REQUEST_TYPE, $requestType);
    }

    /**
     * Guess the request type
     *
     * @param Request $request
     * @return string
     */
    public function guessRequestType(Request $request)
    {
        return $this->guesser->guess($request);
    }
}
