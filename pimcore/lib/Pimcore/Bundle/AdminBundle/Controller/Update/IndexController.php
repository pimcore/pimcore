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
use Pimcore\Config;
use Pimcore\Controller\EventedControllerInterface;
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
     * @Route("/check-debug-mode")
     *
     * @param Request $request
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    public function checkDebugModeAction(Request $request)
    {
        $debug = \Pimcore::inDebugMode() || in_array(Config::getEnvironment(), ['dev', 'test']);

        return $this->json([
            'success' => (bool) $debug
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
        return $this->json([
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
        return $this->json([
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

        return $this->json($availableUpdates);
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

        return $this->json($jobs);
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

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/job-procedural")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function jobProceduralAction(Request $request)
    {
        $status = ['success' => true];

        if ($request->get('type') == 'files') {
            Update::installData($request->get('revision'), $request->get('updateScript'));
        } elseif ($request->get('type') == 'clearcache') {
            \Pimcore\Cache::clearAll();
            \Pimcore\Tool::clearSymfonyCache($this->container);
        } elseif ($request->get('type') == 'preupdate') {
            $status = Update::executeScript($request->get('revision'), 'preupdate');
        } elseif ($request->get('type') == 'postupdate') {
            $status = Update::executeScript($request->get('revision'), 'postupdate');
        } elseif ($request->get('type') == 'cleanup') {
            Update::cleanup();
        } elseif ($request->get('type') == 'composer-update') {
            $options = [];
            if($request->get('no-scripts')) {
                $options[] = '--no-scripts';
            }
            $status = Update::composerUpdate($options);
        } elseif ($request->get('type') == 'composer-invalidate-classmaps') {
            $status = Update::invalidateComposerAutoloadClassmap();
        }

        // we use pure PHP here, otherwise this can cause issues with dependencies that changed during the update
        header('Content-type: application/json');
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
