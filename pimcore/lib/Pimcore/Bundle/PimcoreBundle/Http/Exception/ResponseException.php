<?php

namespace Pimcore\Bundle\PimcoreBundle\Http\Exception;

use Symfony\Component\HttpFoundation\Response;

class ResponseException extends \Exception
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
