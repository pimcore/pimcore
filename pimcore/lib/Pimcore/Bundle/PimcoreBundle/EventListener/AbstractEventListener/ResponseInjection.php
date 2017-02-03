<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener\AbstractEventListener;

use Symfony\Component\HttpFoundation\Response;

abstract class ResponseInjection {

    /**
     * @param Response $response
     * @return bool
     */
    protected function isHtmlResponse (Response $response) {

        if(strpos($response->getContent(), "<html")) {
            return true;
        }

        return false;
    }
}