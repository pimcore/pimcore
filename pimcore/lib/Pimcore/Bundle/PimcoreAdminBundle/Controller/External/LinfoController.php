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

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\External;

use \Linfo\Exceptions\FatalException;
use \Linfo\Linfo;
use \Linfo\Common;
use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

class LinfoController extends AdminController implements EventedControllerInterface
{

    /**
     * @var string
     */
    protected $linfoHome = "";

    /**
     * @Route("/external_linfo/")
     * @param Request $request
     */
    public function indexAction(Request $request)
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
        exit();
    }

    /**
     * @Route("/external_linfo/layout/{anything}", defaults={"anything" = null}, requirements={"anything"=".+"})
     * @param Request $request
     */
    public function layoutAction(Request $request)
    {
        // proxy for resources

        $path = $request->getPathInfo();
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

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        // only for admins
        $this->checkPermission("linfo");

        $this->linfoHome = PIMCORE_PROJECT_ROOT . '/vendor/linfo/linfo/';
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
