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

namespace Pimcore\Bundle\AdminBundle\Controller\GDPR;

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\PimcoreUsers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class PimcoreUsersController
 *
 * @Route("/pimcore-users")
 * @package GDPRDataExtractorBundle\Controller
 */
class PimcoreUsersController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }

    /**
     * @param Request $request
     * @param PimcoreUsers $pimcoreUsers
     * @Route("/search-users")
     */
    public function searchUsersAction(Request $request, PimcoreUsers $pimcoreUsers) {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $result = $pimcoreUsers->searchData(
            intval($allParams['id']),
            strip_tags($allParams['firstname']),
            strip_tags($allParams['lastname']),
            strip_tags($allParams['email']),
            intval($allParams['start']),
            intval($allParams['limit']),
            $allParams['sort']
        );

        return $this->json($result);
    }

    /**
     * @param Request $request
     * @param PimcoreUsers $pimcoreUsers
     * @Route("/export-user-data")
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    public function exportUserDataAction(Request $request, PimcoreUsers $pimcoreUsers) {

        $userData = $pimcoreUsers->getExportData(intval($request->get('id')));

        $jsonResponse = $this->json($userData);
        $jsonResponse->headers->set('Content-Disposition', 'attachment; filename="export-userdata-' . $userData['id'] . '.json"');

        return $jsonResponse;
    }

}
