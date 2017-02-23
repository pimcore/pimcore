<?php

namespace PimcoreLegacyBundle\Controller;

use maff\Zend1MvcPsrMessageBridge\Factory\DiactorosFactory;
use maff\Zend1MvcPsrMessageBridge\Factory\ZendMessageFactory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        try {
            $zendResponse = $legacyKernel->run($zendRequest);
        } catch (\Zend_Controller_Router_Exception $e) {
            if ($e->getMessage()) {
                throw new NotFoundHttpException($e->getMessage(), $e);
            } else {
                throw new NotFoundHttpException('Not Found', $e);
            }
        }

        $psrResponse = $diactorosFactory->createResponse($zendResponse);

        return $psrResponse;
    }
}
