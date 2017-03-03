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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin\External;

use \Linfo\Exceptions\FatalException;
use \Linfo\Linfo;
use \Linfo\Common;
use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Model\Document\Service;
use Pimcore\Tool\Session;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

class AdminerController extends AdminController implements EventedControllerInterface
{

    /**
     * @var string
     */
    protected $linfoHome = "";

    /**
     * @Route("/external_adminer/adminer")
     * @param Request $request
     */
    public function adminerAction()
    {
        $conf = \Pimcore\Config::getSystemConfig()->database->params;
        if (empty($_SERVER["QUERY_STRING"])) {
            return $this->redirect("/admin/external_adminer/adminer?username=" . $conf->username . "&db=" . $conf->dbname);
        }

        chdir($this->adminerHome . "adminer");

        //TODO change to render
        include($this->adminerHome . "adminer/index.php");
        exit;
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
        ini_set("display_errors", 0);

        // only for admins
        $this->checkPermission("adminer");

        // call this to keep the session 'open' so that Adminer can write to it
        $session = Session::get();

        $this->adminerHome = PIMCORE_PROJECT_ROOT . '/vendor/vrana/adminer/';

        // proxy for resources
        $path = $request->getPathInfo();
        $path = str_replace("/admin/external_adminer/", "", $path);
        if (preg_match("@\.(css|js|ico|png|jpg|gif)$@", $path)) {
            $filePath = $this->adminerHome . "/" . $path;

            // it seems that css files need the right content-type (Chrome)
            if (preg_match("@.css$@", $path)) {
                header("Content-Type: text/css");
            } elseif (preg_match("@.js$@", $path)) {
                header("Content-Type: text/javascript");
            }

            if (file_exists($filePath)) {
                echo file_get_contents($filePath);

                if (preg_match("@default.css$@", $path)) {
                    // append custom styles, because in Adminer everything is hardcoded
                    echo file_get_contents($this->adminerHome . "designs/konya/adminer.css");
                    echo file_get_contents(PIMCORE_WEB_ROOT . "/pimcore/static6/css/adminer-modifications.css");
                }
            }

            exit;
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}



// adminer plugin
/**
 * @return AdminerPimcore
 */
function adminer_object()
{
    $pluginDir = PIMCORE_PROJECT_ROOT . "/vendor/vrana/adminer/plugins";

    // required to run any plugin
    include_once $pluginDir . "/plugin.php";

    // autoloader
    foreach (glob($pluginDir . "/*.php") as $filename) {
        include_once $filename;
    }

    $plugins = [
        new AdminerFrames(),
        new AdminerDumpDate,
        new AdminerDumpJson,
        new AdminerDumpBz2,
        new AdminerDumpZip,
        new AdminerDumpXml,
        new AdminerDumpAlter,
    ];

    class AdminerPimcore extends AdminerPlugin
    {
        /**
         * @return string
         */
        public function name()
        {
            return "";
        }

        /**
         * @param bool $create
         * @return string
         */
        public function permanentLogin($create = false)
        {
            // key used for permanent login
            return Session::getSession()->getId();
        }

        /**
         * @param $login
         * @param $password
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
                $host .= ":" . $conf->port;
            }

            // server, username and password for connecting to database
            $result = [
                $host, $conf->username, $conf->password
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
    }

    return new AdminerPimcore($plugins);
}
