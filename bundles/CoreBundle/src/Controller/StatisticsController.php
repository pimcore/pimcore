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

namespace Pimcore\Bundle\CoreBundle\Controller;

use Exception;
use Pimcore\Controller\Controller;
use Pimcore\Security\User\User as PimcoreSecurityUser;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\StatisticsManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * @internal
 */
class StatisticsController extends Controller
{
    /**
     * @throws Exception
     */
    #[Route('/pimcore-statistics', name: 'pimcore_statistics', methods: ['GET'])]
    public function statisticsAction(
        Request $request,
        StatisticsManager $statisticsManager,
        #[CurrentUser] ?UserInterface $securityUser = null,
    ): JsonResponse {
        $user = $securityUser instanceof PimcoreSecurityUser ? $securityUser->getUser() : null;
        $user ??= Authentication::authenticateSession($request);

        if (!$user) {
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
