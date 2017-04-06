<?php

namespace AppBundle\Controller;

use Pimcore\Controller\Configuration\TemplatePhp;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
            'hideLeftNav'  => true,
            'lastUsername' => $lastUsername,
            'error'        => $error,
        ];
    }

    /**
     * Sample route which can only be seen by logged in users.
     *
     * @Route("/{_locale}/secure/user", name="demo_secure_user")
     * @TemplatePhp("Secure/secure.html.php")
     * @Security("has_role('ROLE_USER')")
     */
    public function secureUserAction()
    {
    }

    /**
     * Sample route which can only be seen by logged in admin users.
     *
     * @Route("/{_locale}/secure/admin", name="demo_secure_admin")
     * @TemplatePhp("Secure/secure.html.php")
     */
    public function secureAdminAction()
    {
        // there are multiple ways to control authorization (= what a user is allowed to do):
        //
        // * access_control in your security.yml (see Symfony Security docs)
        // * @Security annotation (see secureUserAction)
        // * isGranted() or denyAccessUnlessGranted() calls in your controller (see Symfony\Bundle\FrameworkBundle\Controller\Controller)
        //

        // this is the same as adding a @Security("has_role('ROLE_ADMIN')") annotation, but gives you more control when
        // to check and what to do
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // another possibility
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('No no');
        }

        return [
            'admin' => true
        ];
    }
}
