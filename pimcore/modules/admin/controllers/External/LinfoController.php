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

class Admin_External_LinfoController extends \Pimcore\Controller\Action\Admin {

    /**
     * @var string
     */
    protected $linfoHome = "";

    public function init() {
        parent::init();

        // only for admins
        $this->checkPermission("linfo");

        $this->linfoHome = PIMCORE_DOCUMENT_ROOT . '/vendor/linfo/linfo/';
    }

    public function indexAction() {
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

    public function layoutAction() {

        // proxy for resources

        $path = $this->getRequest()->getPathInfo();
        $path = str_replace("/admin/external_linfo/", "", $path);

        if(preg_match("@\.(css|js|ico|png|jpg|gif)$@", $path)) {

            if ($path == "layout/styles.css") {
                // aliasing
                $path = "layout/theme_default.css";
            }

            $path = $this->linfoHome . $path;

            if (preg_match("@.css$@", $path)) {
                // it seems that css files need the right content-type (Chrome)
                header("Content-Type: text/css");
            }

            if (file_exists($path)) {
                echo file_get_contents($path);
            }
        }

        exit;
    }
}
