<?php

namespace Pimcore\Event\Traits;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait for all events handling responses. Taken from GetResponseEvent.
 */
trait ResponseAwareTrait
{
    /**
     * The response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * Returns the response object.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets a response and stops event propagation.
     *
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        /** @var $this Event */
        $this->stopPropagation();
    }

    /**
     * Returns whether a response was set.
     *
     * @return bool Whether a response was set
     */
    public function hasResponse()
    {
        return null !== $this->response;
    }
}
