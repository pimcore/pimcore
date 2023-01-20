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

namespace Pimcore\Bundle\GoogleMarketingBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\GoogleMarketingBundle\Config\SiteConfigProvider;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/portal")
 *
 * @internal
 */
class PortalController extends AdminController
{

    /**
     * @Route("/portlet-analytics-sites", name="pimcore_bundle_googlemarketing_portal_portletanalyticssites", methods={"GET"})
     *
     * @param TranslatorInterface $translator
     * @param SiteConfigProvider $siteConfigProvider
     *
     * @return JsonResponse
     */
    public function portletAnalyticsSitesAction(
        TranslatorInterface $translator,
        SiteConfigProvider $siteConfigProvider
    ): JsonResponse {
        $sites = new Site\Listing();
        $data = [
            [
                'id' => 0,
                'site' => $translator->trans('main_site', [], 'admin'),
            ],
        ];

        foreach ($sites->load() as $site) {
            if ($siteConfigProvider->isSiteReportingConfigured($site)) {
                $data[] = [
                    'id' => $site->getId(),
                    'site' => $site->getMainDomain(),
                ];
            }
        }

        return $this->adminJson(['data' => $data]);
    }
}