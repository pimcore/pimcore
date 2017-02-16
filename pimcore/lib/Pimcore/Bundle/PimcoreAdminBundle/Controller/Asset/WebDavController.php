<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Asset;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class WebDavController
{
    /**
     * @Route("/asset/webdav")
     */
    public function webDavAction()
    {
        return new JsonResponse(['webdav' => true]);
    }
}
