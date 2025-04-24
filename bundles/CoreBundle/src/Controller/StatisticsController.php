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

namespace Pimcore\Bundle\CoreBundle\Controller;

use Exception;
use Pimcore\Controller\Controller;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\StatisticsManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class StatisticsController extends Controller
{
    /**
     * @throws Exception
     */
    #[Route('/pimcore-statistics', name: 'pimcore_statistics', methods: ['GET'])]
    public function statisticsAction(Request $request, StatisticsManager $statisticsManager): JsonResponse
    {
        $user = Authentication::authenticateSession($request);
        if (!$user || !$request->isXmlHttpRequest()) {
            throw new AccessDeniedHttpException();
        }

        if ($user->isAdmin()) {
            return $this->json($statisticsManager->getData());
        }

        return $this->json([
            'success' => $statisticsManager->submit(),
        ]);
    }
}
