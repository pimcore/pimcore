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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class CsrfPretectionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;
    use LoggerAwareTrait;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'checkCsrfToken'
        ];
    }

    public function checkCsrfToken(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $exludedRoutes = [
            'pimcore_admin_index', // main view => /admin

            // login
            'pimcore_admin_login', 'pimcore_admin_login_fallback', 'pimcore_admin_login_check', 'pimcore_admin_login_lostpassword',
            'pimcore_admin_login_deeplink', 'pimcore_admin_2fa', 'pimcore_admin_2fa',

            // embeded via <script>, <style>, <img> on /admin
            'pimcore_admin_user_getimage', 'pimcore_admin_misc_admincss',
            'pimcore_admin_misc_jsontranslationssystem', 'pimcore_admin_user_getcurrentuser',
            'pimcore_admin_misc_availablelanguages', 'pimcore_settings_display_custom_logo',

            // thumbnails
            'pimcore_admin_asset_getdocumentthumbnail', 'pimcore_admin_asset_getimagethumbnail',
            'pimcore_admin_asset_getvideothumbnail',

            // previews / versioning
            'pimcore_admin_document_diffversionsimage', 'pimcore_admin_page_display_preview_image',

            // WebDAV
            'pimcore_admin_webdav',

            // external applications
            'pimcore_admin_external_opcache_index',
            'pimcore_admin_external_linfo_index', 'pimcore_admin_external_linfo_layout',
            'pimcore_admin_external_adminer_adminer', 'pimcore_admin_external_adminer_proxy',
            'pimcore_admin_external_adminer_proxy_1', 'pimcore_admin_external_adminer_proxy_2',
        ];

        $route = $request->attributes->get('_route');
        if (in_array($route, $exludedRoutes)) {
            return;
        }

        $csrfToken = Session::useSession(function (AttributeBagInterface $adminSession) {
            return $adminSession->get('csrfToken');
        });

        $requestCsrfToken = $request->headers->get('x_pimcore_csrf_token');
        if (!$requestCsrfToken) {
            $requestCsrfToken = $request->get('csrfToken');
        }

        if (!$csrfToken || $csrfToken !== $requestCsrfToken) {
            $this->logger->error('Detected CSRF attack on {request}', [
                'request' => $request->getPathInfo()
            ]);

            throw new AccessDeniedHttpException('Detected CSRF Attack! Do not do evil things with pimcore ... ;-)');
        }
    }
}
