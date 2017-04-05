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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\External;

use Linfo\Common;
use Linfo\Linfo;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $settings = Common::getVarFromFile($this->linfoHome . 'sample.config.inc.php', 'settings');
        $settings["compress_content"] = false;

        $linfo = new Linfo($settings);
        $linfo->scan();
        $output = new \Linfo\Output\Html($linfo);

        ob_start();
        $output->output();
        $content = ob_get_clean();

        return new Response($content);
    }

    /**
     * @Route("/external_linfo/layout/{anything}", defaults={"anything" = null}, requirements={"anything"=".+"})
     * @param Request $request
     * @return Response
     */
    public function layoutAction(Request $request)
    {
        // proxy for resources

        $response = new Response();
        $path = $request->getPathInfo();
        $path = str_replace("/admin/external_linfo/", "", $path);

        if (preg_match("@\.(css|js|ico|png|jpg|gif)$@", $path)) {
            if ($path == "layout/styles.css") {
                // aliasing
                $path = "layout/theme_default.css";
            }

            $path = $this->linfoHome . $path;

            if (preg_match("@.css$@", $path)) {
                $response->headers->set("Content-Type", "text/css");
            } elseif (preg_match("@.js$@", $path)) {
                $response->headers->set("Content-Type", "text/javascript");
            }

            if (file_exists($path)) {
                $response->setContent(file_get_contents($path));
            }
        }

        return $response;
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
