<?php

namespace LegacyBundle\Controller;

use maff\Zend1MvcPsrMessageBridge\Factory\DiactorosFactory;
use maff\Zend1MvcPsrMessageBridge\Factory\ZendMessageFactory;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class FallbackController
{
    public function fallbackAction(ServerRequestInterface $psrRequest)
    {
        $diactorosFactory = new DiactorosFactory();
        $zendFactory      = new ZendMessageFactory();

        // TODO not implemented yet
        // $zendRequest = $zendFactory->createRequest($psrRequest);
        $zendRequest = new \Zend_Controller_Request_Http();

        $zendResponse = \Pimcore::run(true, $zendRequest);
        $psrResponse  = $diactorosFactory->createResponse($zendResponse);

        return $psrResponse;
    }
}
