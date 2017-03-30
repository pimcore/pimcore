<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class SecureController extends AbstractController
{
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return [
            'lastUsername' => $lastUsername,
            'error'        => $error,
        ];
    }

    /**
     * @Route("/{_locale}/secure/info")
     * @Security("has_role('ROLE_USER')")
     */
    public function secureAction()
    {
    }
}
