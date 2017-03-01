<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Tool\Session;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

class BackupController extends AdminController implements EventedControllerInterface
{
    /**
     * @Route("/backup/init")
     * @param Request $request
     * @return JsonResponse
     */
    public function initAction(Request $request)
    {
        $backup = new \Pimcore\Backup(PIMCORE_BACKUP_DIRECTORY . "/backup_" . date("m-d-Y_H-i") . ".zip");
        $initInfo = $backup->init();

        Session::useSession(function (AttributeBagInterface $session) use ($backup) {
            $session->set('backup', $backup);
        }, "pimcore_backup");

        return new JsonResponse($initInfo);
    }

    /**
     * @Route("/backup/files")
     * @param Request $request
     * @return JsonResponse
     */
    public function filesAction(Request $request)
    {
        $session = $this->getSession();
        $backup = $session->get("backup");
        $return = $backup->fileStep($request->get("step"));

        return new JsonResponse($return);
    }

    /**
     * @Route("/backup/mysql-tables")
     * @param Request $request
     * @return JsonResponse
     */
    public function mysqlTablesAction(Request $request)
    {
        $session = $this->getSession();
        $backup = $session->get("backup");

        $return = $backup->mysqlTables();
        $session->set("backup", $backup);

        return new JsonResponse($return);
    }

    /**
     * @Route("/backup/mysql")
     * @param Request $request
     * @return JsonResponse
     */
    public function mysqlAction(Request $request)
    {
        $name = $request->get("name");
        $type = $request->get("type");

        $session = $this->getSession();
        $backup = $session->get("backup");

        $return = $backup->mysqlData($name, $type);
        $session->set("backup", $backup);

        return new JsonResponse($return);
    }

    /**
     * @Route("/backup/mysql-complete")
     * @param Request $request
     * @return JsonResponse
     */
    public function mysqlCompleteAction(Request $request)
    {
        $session = $this->getSession();
        $backup = $session->get("backup");

        $return = $backup->mysqlComplete();
        $session->set("backup", $backup);

        return new JsonResponse($return);
    }

    /**
     * @Route("/backup/complete")
     * @param Request $request
     * @return JsonResponse
     */
    public function completeAction(Request $request)
    {
        $session = $this->getSession();
        $backup = $session->get("backup");

        $return = $backup->complete();
        $session->set("backup", $backup);

        return new JsonResponse($return);
    }

    /**
     * @Route("/backup/download")
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function downloadAction(Request $request)
    {
        $session = $this->getSession();
        $backup = $session->get("backup");
        $response = new BinaryFileResponse($backup->getBackupFile());
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($backup->getBackupFile()));

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

        $this->checkPermission("backup");

        @ini_set("memory_limit", "-1");
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }

    protected function getSession() {
        $session = Session::get("pimcore_backup");
        return $session;
    }
}
