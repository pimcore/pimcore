<?php

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

namespace Pimcore\Bundle\SystemInfoBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 *
 * @internal
 */
class SettingsController extends AdminController
{
    /**
     * @Route("/phpinfo", name="pimcore_bundle_systeminfo_settings_phpinfo", methods={"GET"})
     *
     * @param Request $request
     * @param Profiler|null $profiler
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function phpinfoAction(Request $request, ?Profiler $profiler): Response
    {
        if ($profiler) {
            $profiler->disable();
        }

        if (!$this->getAdminUser()->isAdmin()) {
            throw new \Exception('Permission denied');
        }

        ob_start();
        phpinfo();
        $content = ob_get_clean();

        return new Response($content);
    }
}
