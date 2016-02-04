<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code. dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

use \Linfo\Exceptions\FatalException;
use \Linfo\Linfo;
use \Linfo\Common;

class Admin_External_AdminerController extends \Pimcore\Controller\Action\Admin
{

    /**
     * @var string
     */
    protected $linfoHome = "";

    public function init()
    {
        parent::init();

        // PHP 7.0 compatibility of adminer (throws some warnings)
        ini_set("display_errors", 0);

        // only for admins
        $this->checkPermission("adminer");

        // call this to keep the session 'open' so that Adminer can write to it
        $session = \Pimcore\Tool\Session::get();

        $this->adminerHome = PIMCORE_DOCUMENT_ROOT . '/vendor/vrana/adminer/';

        // proxy for resources
        $path = $this->getRequest()->getPathInfo();
        $path = str_replace("/admin/external_adminer/", "", $path);
        if (preg_match("@\.(css|js|ico|png|jpg|gif)$@", $path)) {
            $filePath = $this->adminerHome . "/" . $path;

            if (preg_match("@.css$@", $path)) {
                // it seems that css files need the right content-type (Chrome)
                header("Content-Type: text/css");
            }

            if (file_exists($filePath)) {
                echo file_get_contents($filePath);

                if (preg_match("@default.css$@", $path)) {
                    // append custom styles, because in Adminer everything is hardcoded
                    echo file_get_contents($this->adminerHome . "designs/konya/adminer.css");
                    echo file_get_contents(PIMCORE_DOCUMENT_ROOT . "/pimcore/static6/css/adminer-modifications.css");
                }
            }

            exit;
        }
    }

    public function adminerAction()
    {
        $conf = \Pimcore\Config::getSystemConfig()->database->params;
        if (empty($_SERVER["QUERY_STRING"])) {
            $this->redirect("/admin/external_adminer/adminer/?username=" . $conf->username . "&db=" . $conf->dbname);
            exit;
        }

        chdir($this->adminerHome . "adminer");
        include($this->adminerHome . "adminer/index.php");

        $this->removeViewRenderer();
    }
}



// adminer plugin
function adminer_object()
{
    $pluginDir = PIMCORE_DOCUMENT_ROOT . "/vendor/vrana/adminer/plugins";

    // required to run any plugin
    include_once $pluginDir . "/plugin.php";

    // autoloader
    foreach (glob($pluginDir . "/*.php") as $filename) {
        include_once $filename;
    }

    $plugins = array(
        new AdminerFrames(),
        new AdminerDumpDate,
        new AdminerDumpJson,
        new AdminerDumpBz2,
        new AdminerDumpZip,
        new AdminerDumpXml,
        new AdminerDumpAlter,
    );

    class AdminerPimcore extends AdminerPlugin
    {

        public function name()
        {
            return "";
        }

        public function permanentLogin()
        {
            // key used for permanent login
            return \Zend_Session::getId();
        }

        public function login($login, $password)
        {
            return true;
        }

        public function credentials()
        {
            $conf = \Pimcore\Config::getSystemConfig()->database->params;

            $host = $conf->host;
            if ($conf->port) {
                $host .= ":" . $conf->port;
            }

            // server, username and password for connecting to database
            return array(
                $host, $conf->username, $conf->password
            );
        }

        public function database()
        {
            $conf = \Pimcore\Config::getSystemConfig()->database->params;
            // database name, will be escaped by Adminer
            return $conf->dbname;
        }
    }

    return new AdminerPimcore($plugins);
}
