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
use Pimcore\Db\Connection;
use Pimcore\Tool\Requirements;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/install")
 */
class InstallController extends AdminController
{
    /**
     * @Route("/check")
     *
     * @param Request $request
     * @param Connection $db
     *
     * @return Response
     */
    public function checkAction(Request $request, Connection $db)
    {
        $checksPHP   = Requirements::checkPhp();
        $checksFS    = Requirements::checkFilesystem();
        $checksApps  = Requirements::checkExternalApplications();
        $checksMySQL = Requirements::checkMysql($db);

        $viewParams = [
            'checksApps'  => $checksApps,
            'checksPHP'   => $checksPHP,
            'checksMySQL' => $checksMySQL,
            'checksFS'    => $checksFS,
            'headless'    => (bool)$request->get('headless')
        ];

        return $this->render('PimcoreAdminBundle:Admin/Install:check.html.twig', $viewParams);
    }
}
