<?php

namespace Pimcore\Bundle\SystemInfoBundle\Controller;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Tool\Requirements;
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

    /**
     * @Route("/install-check", name="pimcore_bundle_systeminfo_settings_installcheck", methods={"GET", "POST"})
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

        return $this->render('@PimcoreSystemInfo/admin/install/check.html.twig', $viewParams);
    }
}
