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

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\Assets;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AssetController
 *
 * @Route("/asset")
 *
 * @package GDPRDataExtractorBundle\Controller
 *
 * @internal
 */
class AssetController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController implements KernelControllerEventInterface
{

    public function onKernelControllerEvent(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }

    /**
     * @Route("/search-assets", name="pimcore_admin_gdpr_asset_searchasset", methods={"GET"})
     *
     *
     */
    public function searchAssetAction(Request $request, Assets $service): JsonResponse
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $result = $service->searchData(
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
     * @Route("/export", name="pimcore_admin_gdpr_asset_exportassets", methods={"GET"})
     *
     * @param Request $request
     * @param Assets $service
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function exportAssetsAction(Request $request, Assets $service): Response
    {
        $asset = Asset::getById((int) $request->get('id'));
        if (!$asset) {
            throw $this->createNotFoundException('Asset not found');
        }
        if (!$asset->isAllowed('view')) {
            throw $this->createAccessDeniedException('Export denied');
        }
        $exportResult = $service->doExportData($asset);

        return $exportResult;
    }
}
