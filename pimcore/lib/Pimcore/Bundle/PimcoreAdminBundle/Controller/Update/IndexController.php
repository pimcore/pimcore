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

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Update;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Update;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/index")
 */
class IndexController extends AdminController implements EventedControllerInterface
{

    /**
     * @Route("/check-composer-installed")
     * @param Request $request
     * @return JsonResponse
     */
    public function checkComposerInstalledAction(Request $request)
    {
        return $this->json([
            "success" => Update::isComposerAvailable()
        ]);
    }

    /**
     * @Route("/check-file-permissions")
     * @param Request $request
     * @return JsonResponse
     */
    public function checkFilePermissionsAction(Request $request)
    {
        return $this->json([
            "success" => Update::isWriteable()
        ]);
    }

    /**
     * @Route("/get-available-updates")
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableUpdatesAction(Request $request)
    {
        $availableUpdates = Update::getAvailableUpdates();
        return $this->json($availableUpdates);
    }

    /**
     * @Route("/get-jobs")
     * @param Request $request
     * @return JsonResponse
     */
    public function getJobsAction(Request $request)
    {
        $jobs = Update::getJobs($this->getParam("toRevision"));

        return $this->json($jobs);
    }

    /**
     * @Route("/job-parallel")
     * @param Request $request
     * @return JsonResponse
     */
    public function jobParallelAction(Request $request)
    {
        if ($this->getParam("type") == "download") {
            Update::downloadData($this->getParam("revision"), $this->getParam("url"));
        }

        return $this->json(["success" => true]);
    }

    /**
     * @Route("/jobs-procedural")
     * @param Request $request
     * @return mixed
     */
    public function jobProceduralAction(Request $request)
    {
        $status = ["success" => true];

        if ($this->getParam("type") == "files") {
            Update::installData($this->getParam("revision"));
        } elseif ($this->getParam("type") == "clearcache") {
            \Pimcore\Cache::clearAll();
        } elseif ($this->getParam("type") == "preupdate") {
            $status = Update::executeScript($this->getParam("revision"), "preupdate");
        } elseif ($this->getParam("type") == "postupdate") {
            $status = Update::executeScript($this->getParam("revision"), "postupdate");
        } elseif ($this->getParam("type") == "cleanup") {
            Update::cleanup();
        } elseif ($this->getParam("type") == "composer-dump-autoload") {
            $status = Update::composerDumpAutoload();
        }

        // we use pure PHP here, otherwise this can cause issues with dependencies that changed during the update
        header("Content-type: application/json");
        echo json_encode($status);
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

        Update::clearOPCaches();

        $this->checkPermission("update");
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
