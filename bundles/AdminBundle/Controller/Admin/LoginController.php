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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Controller\BruteforceProtectedControllerInterface;
use Pimcore\Bundle\AdminBundle\EventListener\CsrfProtectionListener;
use Pimcore\Bundle\AdminBundle\Security\BruteforceProtectionHandler;
use Pimcore\Config;
use Pimcore\Controller\Configuration\TemplatePhp;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Event\Admin\Login\LostPasswordEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Http\ResponseHelper;
use Pimcore\Logger;
use Pimcore\Model\User;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Tool;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginController extends AdminController implements BruteforceProtectedControllerInterface, EventedControllerInterface
{
    /**
     * @var ResponseHelper
     */
    protected $reponseHelper;

    public function __construct(ResponseHelper $responseHelper)
    {
        $this->reponseHelper = $responseHelper;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        // use browser language for login page if possible
        $locale = 'en';

        $availableLocales = Tool\Admin::getLanguages();
        foreach ($event->getRequest()->getLanguages() as $userLocale) {
            if (in_array($userLocale, $availableLocales)) {
                $locale = $userLocale;
                break;
            }
        }

        $this->get('translator')->setLocale($locale);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('X-Frame-Options', 'deny', true);
        $this->reponseHelper->disableCache($response, true);
    }

    /**
     * @Route("/login", name="pimcore_admin_login")
     * @Route("/login/", name="pimcore_admin_login_fallback")
     *
     * @TemplatePhp()
     */
    public function loginAction(Request $request, CsrfProtectionListener $csrfProtectionListener, Config $config)
    {
        if ($request->get('_route') === 'pimcore_admin_login_fallback') {
            return $this->redirectToRoute('pimcore_admin_login', $request->query->all(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $csrfProtectionListener->regenerateCsrfToken();

        $user = $this->getAdminUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('pimcore_admin_index');
        }

        $view = $this->buildLoginPageViewModel($config);

        $session_gc_maxlifetime = ini_get('session.gc_maxlifetime');
        if (empty($session_gc_maxlifetime)) {
            $session_gc_maxlifetime = 120;
        }

        $view->csrfTokenRefreshInterval = ((int)$session_gc_maxlifetime - 60) * 1000;

        if ($request->get('auth_failed')) {
            $view->error = 'error_auth_failed';
        }
        if ($request->get('session_expired')) {
            $view->error = 'error_session_expired';
        }

        return $view;
    }

    /**
     * @Route("/login/csrf-token", name="pimcore_admin_login_csrf_token")
     */
    public function csrfTokenAction(Request $request, CsrfProtectionListener $csrfProtectionListener)
    {
        $csrfProtectionListener->regenerateCsrfToken();

        return $this->json([
           'csrfToken' => $csrfProtectionListener->getCsrfToken(),
        ]);
    }

    /**
     * @Route("/logout", name="pimcore_admin_logout")
     */
    public function logoutAction()
    {
        // this route will never be matched, but will be handled by the logout handler
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
     * @Route("/login/lostpassword", name="pimcore_admin_login_lostpassword")
     * @TemplatePhp()
     */
    public function lostpasswordAction(Request $request, BruteforceProtectionHandler $bruteforceProtectionHandler, CsrfProtectionListener $csrfProtectionListener, Config $config)
    {
        $view = $this->buildLoginPageViewModel($config);
        $error = null;

        if ($request->getMethod() === 'POST' && $username = $request->get('username')) {
            $user = User::getByName($username);

            if ($user instanceof User) {
                if (!$user->isActive()) {
                    $error = 'user inactive';
                }

                if (!$user->getEmail()) {
                    $error = 'user has no email address';
                }

                if (!$user->getPassword()) {
                    $error = 'user has no password';
                }
            } else {
                $error = 'user unknown';
            }

            if (!$error && $user instanceof User) {
                $token = Authentication::generateToken($user->getName());

                $loginUrl = $this->generateUrl('pimcore_admin_login_check', [
                    'token' => $token,
                    'reset' => 'true',
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                try {
                    $event = new LostPasswordEvent($user, $loginUrl);
                    $this->get('event_dispatcher')->dispatch(AdminEvents::LOGIN_LOSTPASSWORD, $event);

                    // only send mail if it wasn't prevented in event
                    if ($event->getSendMail()) {
                        $mail = Tool::getMail([$user->getEmail()], 'Pimcore lost password service');
                        $mail->setIgnoreDebugMode(true);
                        $mail->setBodyText("Login to pimcore and change your password using the following link. This temporary login link will expire in 24 hours: \r\n\r\n" . $loginUrl);
                        $mail->send();
                    }

                    // directly return event response
                    if ($event->hasResponse()) {
                        return $event->getResponse();
                    }
                } catch (\Exception $e) {
                    $error = 'could not send email';
                }
            }

            if ($error) {
                Logger::error('Lost password service: ' . $error);
                $bruteforceProtectionHandler->addEntry($request->get('username'), $request);
            }
        }

        $csrfProtectionListener->regenerateCsrfToken();

        return $view;
    }

    /**
     * @Route("/login/deeplink", name="pimcore_admin_login_deeplink")
     * @TemplatePhp()
     */
    public function deeplinkAction(Request $request)
    {
        // check for deeplink
        $queryString = $_SERVER['QUERY_STRING'];

        if (preg_match('/(document|asset|object)_([0-9]+)_([a-z]+)/', $queryString, $deeplink)) {
            $deeplink = $deeplink[0];
            $perspective = strip_tags($request->get('perspective'));

            if (strpos($queryString, 'token')) {
                $url = $this->generateUrl('pimcore_admin_login', [
                    'deeplink' => $deeplink,
                    'perspective' => $perspective,
                ]);

                $url .= '&' . $queryString;

                return $this->redirect($url);
            } elseif ($queryString) {
                return new ViewModel([
                    'tab' => $deeplink,
                    'perspective' => $perspective,
                ]);
            }
        }
    }

    /**
     * @return ViewModel
     */
    protected function buildLoginPageViewModel(Config $config)
    {
        $bundleManager = $this->get('pimcore.extension.bundle_manager');

        $view = new ViewModel([
            'config' => $config,
            'pluginCssPaths' => $bundleManager->getCssPaths(),
        ]);

        return $view;
    }

    /**
     * @Route("/login/2fa", name="pimcore_admin_2fa")
     * @TemplatePhp()
     */
    public function twoFactorAuthenticationAction(Request $request, BruteforceProtectionHandler $bruteforceProtectionHandler, Config $config)
    {
        $view = $this->buildLoginPageViewModel($config);

        if ($request->hasSession()) {

            // we have to call the check here manually, because BruteforceProtectionListener uses the 'username' from the request
            $bruteforceProtectionHandler->checkProtection($this->getAdminUser()->getName(), $request);

            $session = $request->getSession();
            $authException = $session->get(Security::AUTHENTICATION_ERROR);
            if ($authException instanceof AuthenticationException) {
                $session->remove(Security::AUTHENTICATION_ERROR);

                $view->error = $authException->getMessage();

                $bruteforceProtectionHandler->addEntry($this->getAdminUser()->getName(), $request);
            }
        } else {
            $view->error = 'No session available, it either timed out or cookies are not enabled.';
        }

        return $view;
    }

    /**
     * @Route("/login/2fa-verify", name="pimcore_admin_2fa-verify")
     *
     * @param Request $request
     */
    public function twoFactorAuthenticationVerifyAction(Request $request)
    {
    }
}
