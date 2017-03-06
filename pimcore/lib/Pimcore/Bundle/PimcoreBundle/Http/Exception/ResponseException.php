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
     * @param Response   $response
     * @param \Exception $previous
     */
    public function __construct(Response $response, \Exception $previous = null)
    {
        parent::__construct('', 0, $previous);

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
