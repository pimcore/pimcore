<?php

namespace LegacyBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class FallbackController
{
    public function fallbackAction()
    {
        $response = \Pimcore::run(true);

        // TODO transform Zend_Response to PSR-7
        return new Response($response->getBody());
    }
}
