<?php

namespace LegacyBundle\Controller;

use maff\Zend1MvcPsrMessageBridge\Factory\DiactorosFactory;
use maff\Zend1MvcPsrMessageBridge\Factory\ZendMessageFactory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FallbackController extends Controller
{
    public function fallbackAction(ServerRequestInterface $psrRequest)
    {
        // initialize
        $legacyKernel = $this->get('pimcore.legacy_kernel');

        $diactorosFactory = new DiactorosFactory();
        $zendFactory      = new ZendMessageFactory();

        // TODO not implemented yet
        // $zendRequest = $zendFactory->createRequest($psrRequest);
        $zendRequest = new \Zend_Controller_Request_Http();

        $zendResponse = $legacyKernel->run($zendRequest);

        $psrResponse = $diactorosFactory->createResponse($zendResponse);

        return $psrResponse;
    }
}
