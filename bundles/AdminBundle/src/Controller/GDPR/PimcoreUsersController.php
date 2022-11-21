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

namespace Pimcore\Bundle\AdminBundle\Controller\GDPR;

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\PimcoreUsers;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Controller\KernelControllerEventInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PimcoreUsersController
 *
 * @Route("/pimcore-users")
 *
 * @internal
 */
class PimcoreUsersController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController implements KernelControllerEventInterface
{

    public function onKernelControllerEvent(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }

    /**
     * @Route("/search-users", name="pimcore_admin_gdpr_pimcoreusers_searchusers", methods={"GET"})
     *
     * @param Request $request
     * @param PimcoreUsers $pimcoreUsers
     *
     * @return JsonResponse
     */
    public function searchUsersAction(Request $request, PimcoreUsers $pimcoreUsers): JsonResponse
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $result = $pimcoreUsers->searchData(
            (int)$allParams['id'],
            strip_tags($allParams['firstname']),
            strip_tags($allParams['lastname']),
            strip_tags($allParams['email']),
            (int)$allParams['start'],
            (int)$allParams['limit'],
            $allParams['sort'] ?? null
        );

        return $this->adminJson($result);
    }

    /**
     * @Route("/export-user-data", name="pimcore_admin_gdpr_pimcoreusers_exportuserdata", methods={"GET"})
     *
     * @param Request $request
     * @param PimcoreUsers $pimcoreUsers
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    public function exportUserDataAction(Request $request, PimcoreUsers $pimcoreUsers): JsonResponse
    {
        $this->checkPermission('users');
        $userData = $pimcoreUsers->getExportData((int)$request->get('id'));

        $json = $this->encodeJson($userData, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);
        $jsonResponse = new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-userdata-' . $userData['id'] . '.json"',
        ], true);

        return $jsonResponse;
    }
}
