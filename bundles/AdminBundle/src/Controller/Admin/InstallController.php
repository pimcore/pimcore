<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Tool\Requirements;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/install")
 *
 * @internal
 */
class InstallController extends AdminController
{
    /**
     * @Route("/check", name="pimcore_admin_install_check", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param Connection $db
     * @param Profiler|null $profiler
     *
     * @return Response
     */
    public function checkAction(Request $request, Connection $db, ?Profiler $profiler): Response
    {
        if ($profiler) {
            $profiler->disable();
        }

        $viewParams = Requirements::checkAll($db);
        $viewParams['headless'] = $request->query->getBoolean('headless') || $request->request->getBoolean('headless');

        return $this->render('@PimcoreAdmin/admin/install/check.html.twig', $viewParams);
    }
}
