<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/info")
 *
 * Contains actions to gather information about the API. The /info/user endpoint
 * is used in tests.
 */
class InfoController extends AbstractRestController
{
    /**
     * @Route("/user")
     */
    public function userAction()
    {
        return $this->createSuccessResponse([
            'user' => $this->getUser()->getUsername()
        ]);
    }
}
