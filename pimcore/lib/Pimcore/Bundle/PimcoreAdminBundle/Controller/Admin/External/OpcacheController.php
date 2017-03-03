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

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

class OpcacheController extends AdminController implements EventedControllerInterface
{

    /**
     * @Route("/external_opcache")
     * @param Request $request
     */
    public function indexAction(Request $request)
    {
        $path = PIMCORE_PROJECT_ROOT . '/vendor/amnuts/opcache-gui';

        include($path . "/index.php");
        die();
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
        $this->checkPermission("opcache");
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }

}
