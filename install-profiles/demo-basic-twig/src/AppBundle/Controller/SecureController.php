<?php

namespace AppBundle\Controller;

use AppBundle\Form\LoginFormType;
use AppBundle\Model\DataObject\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecureController extends FrontendController
{
    public function loginAction(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ) {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $formData = [
            '_username'    => $lastUsername,
            '_target_path' => '/' . $request->getLocale()
        ];

        $form = $this->createForm(LoginFormType::class, $formData, [
            'action' => $this->generateUrl('demo_login'),
        ]);

        return [
            'hideLeftNav'     => true,
            'showBreadcrumbs' => false,
            'form'            => $form->createView(),
            'error'           => $error,
            'availableUsers'  => $this->loadAvailableUsers()
        ];
    }

    /**
     * This is only for DEMO purposes - show a list of available users. Obviously you do NOT want
     * this in your real application.
     *
     * @return array
     */
    private function loadAvailableUsers()
    {
        /** @var User[] $users */
        $users = User::getList();

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'username' => $user->getUsername(),
                'roles'    => $user->getRoles(),
                'password' => 'doe'
            ];
        }

        return $result;
    }

    /**
     * Sample route which can only be seen by logged in users.
     *
     * @Route("/{_locale}/secure/user", name="demo_secure_user")
     * @Template("Secure/secure.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function secureUserAction()
    {
        return [
            'showBreadcrumbs' => false,
        ];
    }

    /**
     * Sample route which can only be seen by logged in admin users.
     *
     * @Route("/{_locale}/secure/admin", name="demo_secure_admin")
     * @Template("Secure/secure.html.twig")
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
