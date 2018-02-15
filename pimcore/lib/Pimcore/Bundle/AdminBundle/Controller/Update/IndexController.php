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

namespace Pimcore\Bundle\AdminBundle\Controller\Update;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Update;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/index")
 */
class IndexController extends AdminController implements EventedControllerInterface
{
    /**
     * @Route("/check-debug-mode")
     *
     * @param KernelInterface $kernel
     *
     * @return JsonResponse
     */
    public function checkDebugModeAction(KernelInterface $kernel)
    {
        return $this->adminJson([
            'success' => $kernel->isDebug()
        ]);
    }

    /**
     * @Route("/check-composer-installed")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkComposerInstalledAction(Request $request)
    {
        return $this->adminJson([
            'success' => Update::isComposerAvailable()
        ]);
    }

    /**
     * @Route("/check-file-permissions")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkFilePermissionsAction(Request $request)
    {
        return $this->adminJson([
            'success' => Update::isWriteable()
        ]);
    }

    /**
     * @Route("/get-available-updates")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableUpdatesAction(Request $request)
    {
        $availableUpdates = Update::getAvailableUpdates();

        return $this->adminJson($availableUpdates);
    }

    /**
     * @Route("/get-jobs")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getJobsAction(Request $request)
    {
        $jobs = Update::getJobs($request->get('toRevision'));

        return $this->adminJson($jobs);
    }

    /**
     * @Route("/job-parallel")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function jobParallelAction(Request $request)
    {
        if ($request->get('type') == 'download') {
            Update::downloadData($request->get('revision'), $request->get('url'));
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/job-procedural")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function jobProceduralAction(Request $request, KernelInterface $kernel)
    {
        $status = ['success' => true];

        if ($request->get('type') == 'files') {
            Update::installData($request->get('revision'), $request->get('updateScript'));
        } elseif ($request->get('type') == 'clearcache') {
            \Pimcore\Cache::clearAll();
            \Pimcore\Update::clearSymfonyCaches();
        } elseif ($request->get('type') == 'preupdate') {
            $status = Update::executeScript($request->get('revision'), 'preupdate');
        } elseif ($request->get('type') == 'postupdate') {
            $status = Update::executeScript($request->get('revision'), 'postupdate');
        } elseif ($request->get('type') == 'cleanup') {
            Update::cleanup();
        } elseif ($request->get('type') == 'composer-update') {
            $options = [];
            if ($request->get('no-scripts')) {
                $options[] = '--no-scripts';
            }
            $status = Update::composerUpdate($options);
        } elseif ($request->get('type') == 'composer-invalidate-classmaps') {
            $status = Update::invalidateComposerAutoloadClassmap();
        }

        // we send the response directly here, otherwise this can cause issues with dependencies that changed during the update
        $response = new JsonResponse($status);
        $response->sendHeaders();
        $response->sendContent();

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

        $this->checkPermission('update');
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
