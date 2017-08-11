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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\External {

    use Pimcore\Bundle\AdminBundle\Controller\AdminController;
    use Pimcore\Controller\EventedControllerInterface;
    use Pimcore\Tool\Session;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
    use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
    use Symfony\Component\Routing\Annotation\Route;

    class AdminerController extends AdminController implements EventedControllerInterface
    {
        /**
         * @var string
         */
        protected $adminerHome = '';

        /**
         * @Route("/external_adminer/adminer")
         *
         * @return Response
         */
        public function adminerAction()
        {
            $conf = \Pimcore\Config::getSystemConfig()->database->params;
            if (empty($_SERVER['QUERY_STRING'])) {
                return $this->redirect('/admin/external_adminer/adminer?username=' . $conf->username . '&db=' . $conf->dbname);
            }

            chdir($this->adminerHome . 'adminer');

            ob_start();
            include($this->adminerHome . 'adminer/index.php');
            $content = ob_get_clean();

            $content = str_replace('"../adminer/', '"proxy/adminer/', $content);
            $content = str_replace('"static/', '"proxy/adminer/static/', $content);
            $content = str_replace('"../externals/', '"proxy/externals/', $content);

            return new Response($content, 200, $this->getAdminerSetHeaders());
        }

        /**
         * @Route("/external_adminer/proxy/{path}", requirements={"path"=".*"})
         *
         * @param Request $request
         *
         * @return Response
         */
        public function proxyAction(Request $request)
        {
            $content = '';
            $headers = [];

            // proxy for resources
            $path = $request->get('path');
            if (preg_match("@\.(css|js|ico|png|jpg|gif)$@", $path)) {
                $filePath = $this->adminerHome . '/' . $path;

                // it seems that css files need the right content-type (Chrome)
                if (preg_match('@.css$@', $path)) {
                    $headers['Content-Type'] = 'text/css';
                } elseif (preg_match('@.js$@', $path)) {
                    $headers['Content-Type'] = 'text/javascript';
                }

                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);

                    if (preg_match('@default.css$@', $path)) {
                        // append custom styles, because in Adminer everything is hardcoded
                        $content .= file_get_contents($this->adminerHome . 'designs/konya/adminer.css');
                        $content .= file_get_contents(PIMCORE_WEB_ROOT . '/pimcore/static6/css/adminer-modifications.css');
                    }
                }
            }

            return new Response($content, 200, array_merge($headers, $this->getAdminerSetHeaders()));
        }

        /**
         * @param FilterControllerEvent $event
         */
        public function onKernelController(FilterControllerEvent $event)
        {
            $isMasterRequest = $event->isMasterRequest();
            if (!$isMasterRequest) {
                return;
            }

            $request = $event->getRequest();

            // PHP 7.0 compatibility of adminer (throws some warnings)
            ini_set('display_errors', 0);

            // only for admins
            $this->checkPermission('adminer');

            // call this to keep the session 'open' so that Adminer can write to it
            $session = Session::get();

            $this->adminerHome = PIMCORE_PROJECT_ROOT . '/vendor/vrana/adminer/';
        }

        /**
         * @param FilterResponseEvent $event
         */
        public function onKernelResponse(FilterResponseEvent $event)
        {
            // nothing to do
        }

        /**
         * @return array
         */
        protected function getAdminerSetHeaders()
        {
            $headers = [];

            if (!headers_sent()) {
                $headersRaw = headers_list();

                foreach ($headersRaw as $header) {
                    $header = explode(":", $header);
                    list($headerKey, $headerValue) = $header;

                    if ($headerKey && $headerValue) {
                        $headers[$headerKey] = $headerValue;
                    }
                }

                header_remove();
            }

            return $headers;
        }
    }
}

namespace {

    use Pimcore\Cache;
    use Pimcore\Tool\Session;

    if (!function_exists('adminer_object')) {
        // adminer plugin
        /**
         * @return AdminerPimcore
         */
        function adminer_object()
        {
            $pluginDir = PIMCORE_PROJECT_ROOT . '/vendor/vrana/adminer/plugins';

            // required to run any plugin
            include_once $pluginDir . '/plugin.php';

            // autoloader
            foreach (glob($pluginDir . '/*.php') as $filename) {
                include_once $filename;
            }

            $plugins = [
                new \AdminerFrames(),
                new \AdminerDumpDate,
                new \AdminerDumpJson,
                new \AdminerDumpBz2,
                new \AdminerDumpZip,
                new \AdminerDumpXml,
                new \AdminerDumpAlter,
            ];

            class AdminerPimcore extends \AdminerPlugin
            {
                /**
                 * @return string
                 */
                public function name()
                {
                    return '';
                }

                /**
                 * @param bool $create
                 *
                 * @return string
                 */
                public function permanentLogin($create = false)
                {
                    // key used for permanent login
                    return Session::getSessionId();
                }

                /**
                 * @param $login
                 * @param $password
                 *
                 * @return bool
                 */
                public function login($login, $password)
                {
                    return true;
                }

                /**
                 * @return array
                 */
                public function credentials()
                {
                    $conf = \Pimcore\Config::getSystemConfig()->database->params;

                    $host = $conf->host;
                    if ($conf->port) {
                        $host .= ':' . $conf->port;
                    }

                    // server, username and password for connecting to database
                    $result = [
                        $host,
                        $conf->username,
                        $conf->password
                    ];

                    return $result;
                }

                /**
                 * @return mixed
                 */
                public function database()
                {
                    $conf = \Pimcore\Config::getSystemConfig()->database->params;
                    // database name, will be escaped by Adminer
                    return $conf->dbname;
                }

                public function databases($flush = true)
                {
                    $cacheKey = 'pimcore_adminer_databases';

                    if (!$return = Cache::load($cacheKey)) {
                        $db = Pimcore\Db::get();
                        $return = $db->fetchAll('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA');

                        foreach ($return as &$ret) {
                            $ret = $ret['SCHEMA_NAME'];
                        }

                        Cache::save($return, $cacheKey);
                    }

                    return $return;
                }
            }

            return new AdminerPimcore($plugins);
        }
    }
}
