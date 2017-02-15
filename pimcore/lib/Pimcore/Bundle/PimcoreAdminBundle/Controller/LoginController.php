<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginController extends Controller
{
    /**
     * @Route("/login", name="admin_login")
     * @Template()
     */
    public function loginAction()
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('admin_index');
        }

        /** @var AuthenticationException $exception */
        $exception = $this->get('security.authentication_utils')
            ->getLastAuthenticationError();

        return [
            'error' => $exception ? $exception->getMessage() : null,
        ];
    }

    /**
     * @Route("/logout", name="admin_logout")
     */
    public function logoutAction()
    {
        return new Response('logout');
    }
}
