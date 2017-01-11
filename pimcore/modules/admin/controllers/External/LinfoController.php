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

use \Linfo\Exceptions\FatalException;
use \Linfo\Linfo;
use \Linfo\Common;

class Admin_External_LinfoController extends \Pimcore\Controller\Action\Admin
{

    /**
     * @var string
     */
    protected $linfoHome = "";

    public function init()
    {
        parent::init();

        // only for admins
        $this->checkPermission("linfo");

        $this->linfoHome = PIMCORE_DOCUMENT_ROOT . '/vendor/linfo/linfo/';
    }

    public function indexAction()
    {
        try {
            $settings = Common::getVarFromFile($this->linfoHome . 'sample.config.inc.php', 'settings');
            $settings["compress_content"] = false;

            $linfo = new Linfo($settings);
            $linfo->scan();
            $output = new \Linfo\Output\Html($linfo);
            $output->output();
        } catch (FatalException $e) {
            echo $e->getMessage()."\n";
            exit(1);
        }

        $this->removeViewRenderer();
    }

    public function layoutAction()
    {

        // proxy for resources

        $path = $this->getRequest()->getPathInfo();
        $path = str_replace("/admin/external_linfo/", "", $path);

        if (preg_match("@\.(css|js|ico|png|jpg|gif)$@", $path)) {
            if ($path == "layout/styles.css") {
                // aliasing
                $path = "layout/theme_default.css";
            }

            $path = $this->linfoHome . $path;

            if (preg_match("@.css$@", $path)) {
                // it seems that css files need the right content-type (Chrome)
                header("Content-Type: text/css");
            } elseif (preg_match("@.js$@", $path)) {
                header("Content-Type: text/javascript");
            }

            if (file_exists($path)) {
                echo file_get_contents($path);
            }
        }

        exit;
    }
}
