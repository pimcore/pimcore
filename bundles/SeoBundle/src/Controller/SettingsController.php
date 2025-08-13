<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\SeoBundle\Controller;

use Pimcore\Bundle\SeoBundle\Config;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/robots-txt', name: 'pimcore_bundle_seo_settings_robotstxtget', methods: ['GET'])]
    public function robotsTxtGetAction(): JsonResponse
    {
        $this->checkPermission('robots.txt');

        $config = Config::getRobotsConfig();

        return $this->jsonResponse([
            'success' => true,
            'data' => $config,
            'onFileSystem' => file_exists(PIMCORE_WEB_ROOT . '/robots.txt'),
        ]);
    }

    #[Route('/robots-txt', name: 'pimcore_bundle_seo_settings_robotstxtput', methods: ['PUT'])]
    public function robotsTxtPutAction(Request $request): JsonResponse
    {
        $this->checkPermission('robots.txt');

        $values = $request->request->all('data');

        foreach ($values as $siteId => $robotsContent) {
            SettingsStore::set('robots.txt-' . $siteId, $robotsContent, SettingsStore::TYPE_STRING, 'robots.txt');
        }

        return $this->jsonResponse([
            'success' => true,
        ]);
    }
}
