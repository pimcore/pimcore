<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Controller\BruteforceProtectedControllerInterface;
use Pimcore\Bundle\AdminBundle\Security\Authenticator\AdminLoginAuthenticator;
use Pimcore\Bundle\AdminBundle\Security\BruteforceProtectionHandler;
use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Config;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\KernelResponseEventInterface;
use Pimcore\Event\Admin\Login\LoginRedirectEvent;
use Pimcore\Event\Admin\Login\LostPasswordEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Http\ResponseHelper;
use Pimcore\Logger;
use Pimcore\Model\User;
use Pimcore\Security\SecurityHelper;
use Pimcore\Tool;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * @internal
 */
class LoginController extends AdminController implements BruteforceProtectedControllerInterface, KernelControllerEventInterface, KernelResponseEventInterface
{
    public function __construct(
        protected ResponseHelper $responseHelper,
    ) {
    }

    /**
     * @param ControllerEvent $event
     */
    public function onKernelControllerEvent(ControllerEvent $event)
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

        if ($this->getTranslator() instanceof LocaleAwareInterface) {
            $this->getTranslator()->setLocale($locale);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelResponseEvent(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('X-Frame-Options', 'deny', true);
        $this->responseHelper->disableCache($response, true);
    }

    /**
     * @Route("/login", name="pimcore_admin_login")
     * @Route("/login/", name="pimcore_admin_login_fallback")
     */
    public function loginAction(Request $request, CsrfProtectionHandler $csrfProtection, Config $config)
    {
        if ($request->get('_route') === 'pimcore_admin_login_fallback') {
            return $this->redirectToRoute('pimcore_admin_login', $request->query->all(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $csrfProtection->regenerateCsrfToken();

        $user = $this->getAdminUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('pimcore_admin_index');
        }

        $params = $this->buildLoginPageViewParams($config);

        $session_gc_maxlifetime = ini_get('session.gc_maxlifetime');
        if (empty($session_gc_maxlifetime)) {
            $session_gc_maxlifetime = 120;
        }

        $params['csrfTokenRefreshInterval'] = ((int)$session_gc_maxlifetime - 60) * 1000;

        if ($request->get('too_many_attempts')) {
            $params['error'] = SecurityHelper::convertHtmlSpecialChars($request->get('too_many_attempts'));
        }
        if ($request->get('auth_failed')) {
            $params['error'] = 'error_auth_failed';
        }
        if ($request->get('session_expired')) {
            $params['error'] = 'error_session_expired';
        }
        if ($request->get('deeplink')) {
            $params['deeplink'] = true;
        }

        $params['browserSupported'] = $this->detectBrowser();
        $params['debug'] = \Pimcore::inDebugMode();

        return $this->render('@PimcoreAdmin/Admin/Login/login.html.twig', $params);
    }

    /**
     * @Route("/login/csrf-token", name="pimcore_admin_login_csrf_token")
     */
    public function csrfTokenAction(Request $request, CsrfProtectionHandler $csrfProtection)
    {
        if (!$this->getAdminUser()) {
            $csrfProtection->regenerateCsrfToken();
        }

        return $this->json([
           'csrfToken' => $csrfProtection->getCsrfToken(),
        ]);
    }

    /**
     * @Route("/logout", name="pimcore_admin_logout" , methods={"POST"})
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
     * @see AdminLoginAuthenticator for the security implementation
     * @see AdminAuthenticator for the security implementation (Authenticator Based Security)
     */
    public function loginCheckAction()
    {
        // just in case the authenticator didn't redirect
        return new RedirectResponse($this->generateUrl('pimcore_admin_login'));
    }

    /**
     * @Route("/login/lostpassword", name="pimcore_admin_login_lostpassword")
     */
    public function lostpasswordAction(Request $request, ?BruteforceProtectionHandler $bruteforceProtectionHandler, CsrfProtectionHandler $csrfProtection, Config $config, EventDispatcherInterface $eventDispatcher, RateLimiterFactory $resetPasswordLimiter)
    {
        $params = $this->buildLoginPageViewParams($config);
        $error = null;

        if ($request->getMethod() === 'POST' && $username = $request->get('username')) {
            $user = User::getByName($username);
            if (!$user instanceof User) {
                $error = 'user_unknown';
            }

            // TODO Pimcore 11: remove this BC layer, only the RateLimiter would be valid
            if ($bruteforceProtectionHandler) {
                try {
                    $bruteforceProtectionHandler->checkProtection($username, $request);
                } catch (\Exception $e) {
                    $error = 'user_reset_password_too_many_attempts';
                }
            } else {
                $limiter = $resetPasswordLimiter->create($request->getClientIp());

                if (false === $limiter->consume(1)->isAccepted()) {
                    $error = 'user_reset_password_too_many_attempts';
                }
            }

            if (!$error) {
                if (!$user->isActive()) {
                    $error = 'user_inactive';
                }
                if (!$user->getEmail()) {
                    $error = 'user_no_email_address';
                }
                if (!$user->getPassword()) {
                    $error = 'user_no_password';
                }
            }

            if (!$error) {
                $token = Authentication::generateToken($user->getName());

                $loginUrl = $this->generateUrl('pimcore_admin_login_check', [
                    'token' => $token,
                    'reset' => 'true',
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                try {
                    $event = new LostPasswordEvent($user, $loginUrl);
                    $eventDispatcher->dispatch($event, AdminEvents::LOGIN_LOSTPASSWORD);

                    // only send mail if it wasn't prevented in event
                    if ($event->getSendMail()) {
                        $mail = Tool::getMail([$user->getEmail()], 'Pimcore lost password service');
                        $mail->setIgnoreDebugMode(true);
                        $mail->text("Login to pimcore and change your password using the following link. This temporary login link will expire in 24 hours: \r\n\r\n" . $loginUrl);
                        $mail->send();
                    }

                    // directly return event response
                    if ($event->hasResponse()) {
                        return $event->getResponse();
                    }
                } catch (\Exception $e) {
                    Logger::error('Error sending password recovery email: ' . $e->getMessage());
                    $error = 'lost_password_email_error';
                }
            }

            if ($error) {
                Logger::error('Lost password service: ' . $error);
                $bruteforceProtectionHandler?->addEntry($request->get('username'), $request);
            }
        }

        $csrfProtection->regenerateCsrfToken();

        if ($error) {
            $params['reset_error'] = 'Please make sure you are entering a correct input.';
            if ($error === 'user_reset_password_too_many_attempts') {
                $params['reset_error'] = 'Too many attempts. Please retry later.';
            }
        }

        return $this->render('@PimcoreAdmin/Admin/Login/lostpassword.html.twig', $params);
    }

    /**
     * @Route("/login/deeplink", name="pimcore_admin_login_deeplink")
     */
    public function deeplinkAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        // check for deeplink
        $queryString = $_SERVER['QUERY_STRING'];

        if (preg_match('/(document|asset|object)_([0-9]+)_([a-z]+)/', $queryString, $deeplink)) {
            $deeplink = $deeplink[0];
            $perspective = strip_tags($request->get('perspective', ''));

            if (strpos($queryString, 'token')) {
                $event = new LoginRedirectEvent('pimcore_admin_login', [
                    'deeplink' => $deeplink,
                    'perspective' => $perspective,
                ]);
                $eventDispatcher->dispatch($event, AdminEvents::LOGIN_REDIRECT);

                $url = $this->generateUrl($event->getRouteName(), $event->getRouteParams());
                $url .= '&' . $queryString;

                return $this->redirect($url);
            } elseif ($queryString) {
                $event = new LoginRedirectEvent('pimcore_admin_login', [
                    'deeplink' => 'true',
                    'perspective' => $perspective,
                ]);
                $eventDispatcher->dispatch($event, AdminEvents::LOGIN_REDIRECT);

                return $this->render('@PimcoreAdmin/Admin/Login/deeplink.html.twig', [
                    'tab' => $deeplink,
                    'redirect' => $this->generateUrl($event->getRouteName(), $event->getRouteParams()),
                ]);
            }
        }
    }

    protected function buildLoginPageViewParams(Config $config): array
    {
        return [
            'config' => $config,
            'pluginCssPaths' => $this->getBundleManager()->getCssPaths(),
        ];
    }

    /**
     * @Route("/login/2fa", name="pimcore_admin_2fa")
     */
    public function twoFactorAuthenticationAction(Request $request, ?BruteforceProtectionHandler $bruteforceProtectionHandler, Config $config)
    {
        $params = $this->buildLoginPageViewParams($config);

        if ($request->hasSession()) {
            // we have to call the check here manually, because BruteforceProtectionListener uses the 'username' from the request
            $bruteforceProtectionHandler?->checkProtection($this->getAdminUser()->getName(), $request);

            $session = $request->getSession();
            $authException = $session->get(Security::AUTHENTICATION_ERROR);
            if ($authException instanceof AuthenticationException) {
                $session->remove(Security::AUTHENTICATION_ERROR);

                $params['error'] = $authException->getMessage();

                $bruteforceProtectionHandler?->addEntry($this->getAdminUser()->getName(), $request);
            }
        } else {
            $params['error'] = 'No session available, it either timed out or cookies are not enabled.';
        }

        return $this->render('@PimcoreAdmin/Admin/Login/twoFactorAuthentication.html.twig', $params);
    }

    /**
     * @Route("/login/2fa-verify", name="pimcore_admin_2fa-verify")
     *
     * @param Request $request
     */
    public function twoFactorAuthenticationVerifyAction(Request $request)
    {
    }

    /**
     * @return bool
     */
    public function detectBrowser()
    {
        $supported = false;
        $browser = new \Browser();
        $browserVersion = (int)$browser->getVersion();

        if ($browser->getBrowser() == \Browser::BROWSER_FIREFOX && $browserVersion >= 72) {
            $supported = true;
        }
        if ($browser->getBrowser() == \Browser::BROWSER_CHROME && $browserVersion >= 84) {
            $supported = true;
        }
        if ($browser->getBrowser() == \Browser::BROWSER_SAFARI && $browserVersion >= 13.1) {
            $supported = true;
        }
        if ($browser->getBrowser() == \Browser::BROWSER_EDGE && $browserVersion >= 90) {
            $supported = true;
        }

        return $supported;
    }
}
