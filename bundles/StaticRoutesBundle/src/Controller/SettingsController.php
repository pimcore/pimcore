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

namespace Pimcore\Bundle\StaticRoutesBundle\Controller;

use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Model\Exception\ConfigWriteException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @Route("/staticroutes", name="pimcore_bundle_staticroutes_settings_staticroutes", methods={"POST"})
     *
     *
     */
    public function staticroutesAction(Request $request): JsonResponse
    {
        if ($request->request->has('data')) {
            $this->checkPermission('routes');

            $data = $this->decodeJson($request->request->getString('data'));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                }
            }

            if ($request->query->getString('xaction') == 'destroy') {
                $id = $data['id'];
                $route = Staticroute::getById($id);
                if (!$route->isWriteable()) {
                    throw new ConfigWriteException();
                }
                $route->delete();

                return $this->jsonResponse(['success' => true, 'data' => []]);
            } elseif ($request->query->getString('xaction') == 'update') {
                // save routes
                $route = Staticroute::getById($data['id']);
                if (!$route->isWriteable()) {
                    throw new ConfigWriteException();
                }

                $route->setValues($data);

                $route->save();

                return $this->jsonResponse(['data' => $route->getObjectVars(), 'success' => true]);
            } elseif ($request->query->getString('xaction') == 'create') {
                if (!(new Staticroute())->isWriteable()) {
                    throw new ConfigWriteException();
                }
                unset($data['id']);

                // save route
                $route = new Staticroute();
                $route->setValues($data);

                $route->save();

                $responseData = $route->getObjectVars();
                $responseData['writeable'] = $route->isWriteable();

                return $this->jsonResponse(['data' => $responseData, 'success' => true]);
            }
        } else {
            // get list of routes

            $list = new Staticroute\Listing();

            if ($filter = $request->request->getString('filter')) {
                $list->setFilter(function (Staticroute $staticRoute) use ($filter) {
                    foreach ($staticRoute->getObjectVars() as $value) {
                        if (!is_scalar($value)) {
                            continue;
                        }
                        if (stripos((string)$value, $filter) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            $routes = [];
            foreach ($list->getRoutes() as $routeFromList) {
                $route = $routeFromList->getObjectVars();
                $route['writeable'] = $routeFromList->isWriteable();
                $route['siteId'] = implode(',', $routeFromList->getSiteId());
                $routes[] = $route;
            }

            return $this->jsonResponse(['data' => $routes, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->jsonResponse(['success' => false]);
    }
}
