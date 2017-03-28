<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreAdminBundle\Controller\BruteforceProtectedControllerInterface;
use Pimcore\Config;
use Pimcore\Controller\Configuration\TemplatePhp;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Event\Admin\Login\LostPasswordEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Model\User;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Tool;
use Pimcore\Tool\Authentication;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginController extends AdminController implements BruteforceProtectedControllerInterface, EventedControllerInterface
{
    public function onKernelController(FilterControllerEvent $event)
    {
        // use browser language for login page if possible
        $locale = "en";

        $availableLocales = Tool\Admin::getLanguages();
        foreach ($event->getRequest()->getLanguages() as $userLocale) {
            if (in_array($userLocale, $availableLocales)) {
                $locale = $userLocale;
                break;
            }
        }

        $this->get("translator")->setLocale($locale);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
    }

    /**
     * @Route("/login", name="pimcore_admin_login")
     * @TemplatePhp()
     */
    public function loginAction(Request $request)
    {
        if (!is_file(\Pimcore\Config::locateConfigFile("system.php"))) {
            return $this->redirect("/install.php");
        }


        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('pimcore_admin_index');
        }

        $view = $this->buildLoginPageViewModel();

        if ($request->get('auth_failed')) {
            $view->error = 'error_auth_failed';
        }
        if ($request->get('session_expired')) {
            $view->error = 'error_session_expired';
        }

        return $view;
    }

    /**
     * Dummy route used to check authentication
     *
     * @Route("/login/login", name="pimcore_admin_login_check")
     *
     * @see AdminAuthenticator for the security implementation
     */
    public function loginCheckAction()
    {
        // just in case the authenticator didn't redirect
        return new RedirectResponse($this->generateUrl('pimcore_admin_login'));
    }

    /**
     * @Route("/login/lostpassword")
     * @TemplatePhp()
     */
    public function lostpasswordAction(Request $request)
    {
        $view = $this->buildLoginPageViewModel();
        $view->success = false;

        // TODO is the error on the view used somewhere?
        if ($request->getMethod() === 'POST' && $username = $request->get("username")) {
            $user = User::getByName($username);

            if (!$user instanceof User) {
                $view->error = "user unknown";
            } else {
                if ($user->isActive()) {
                    if ($user->getEmail()) {
                        $token = Authentication::generateToken($username, $user->getPassword());

                        $loginUrl = $this->generateUrl('pimcore_admin_login_check', [
                            'username' => $username,
                            'token'    => $token,
                            'reset'    => 'true'
                        ], UrlGeneratorInterface::ABSOLUTE_URL);

                        try {
                            $event = new LostPasswordEvent($user, $loginUrl);
                            $this->get('event_dispatcher')->dispatch(AdminEvents::LOGIN_LOSTPASSWORD, $event);

                            // only send mail if it wasn't prevented in event
                            if ($event->getSendMail()) {
                                $mail = Tool::getMail([$user->getEmail()], "Pimcore lost password service");
                                $mail->setIgnoreDebugMode(true);
                                $mail->setBodyText("Login to pimcore and change your password using the following link. This temporary login link will expire in 30 minutes: \r\n\r\n" . $loginUrl);
                                $mail->send();
                            }

                            // directly return event response
                            if ($event->hasResponse()) {
                                return $event->getResponse();
                            }

                            $view->success = true;
                        } catch (\Exception $e) {
                            $view->error = "could not send email";
                        }
                    } else {
                        $view->error = "user has no email address";
                    }
                } else {
                    $view->error = "user inactive";
                }
            }
        }

        return $view;
    }

    /**
     * @Route("/login/deeplink")
     * @TemplatePhp()
     */
    public function deeplinkAction()
    {
        // check for deeplink
        $queryString = $_SERVER["QUERY_STRING"];

        if (preg_match("/(document|asset|object)_([0-9]+)_([a-z]+)/", $queryString, $deeplink)) {
            if (strpos($queryString, "token")) {
                $deeplink = $deeplink[0];
                $url = $this->generateUrl('pimcore_admin_login', [
                    'deeplink' => $deeplink
                ]);

                $url .= '&' . $queryString;

                return $this->redirect($url);
            } elseif ($queryString) {
                return new ViewModel([
                    'tab' => $queryString
                ]);
            }
        }
    }

    /**
     * @return ViewModel
     */
    protected function buildLoginPageViewModel()
    {
        $bundleManager = $this->get('pimcore.extension.bundle_manager');

        $view = new ViewModel([
            'config'         => Config::getSystemConfig(),
            'pluginCssPaths' => $bundleManager->getCssPaths()
        ]);

        return $view;
    }
}
