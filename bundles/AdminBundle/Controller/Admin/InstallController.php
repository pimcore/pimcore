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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Tool\Requirements;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/install")
 */
class InstallController extends AdminController
{
    /**
     * @Route("/check", name="pimcore_admin_install_check", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param ConnectionInterface $db
     * @param Profiler $profiler
     *
     * @return Response
     */
    public function checkAction(Request $request, ConnectionInterface $db, ?Profiler $profiler)
    {
        if ($profiler) {
            $profiler->disable();
        }

        $viewParams = Requirements::checkAll($db);
        $viewParams['headless'] = (bool)$request->get('headless');

        return $this->render('PimcoreAdminBundle:Admin/Install:check.html.twig', $viewParams);
    }
}
