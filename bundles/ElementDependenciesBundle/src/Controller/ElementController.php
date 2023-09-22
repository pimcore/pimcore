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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\ElementDependenciesBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Pimcore\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 *
 * @internal
 */
class ElementController extends AdminAbstractController
{
    /**
     * @Route("/element/get-requires-dependencies", name="pimcore_admin_element_getrequiresdependencies", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getRequiresDependenciesAction(Request $request): JsonResponse
    {
        $id = $request->query->getInt('id');
        $type = $request->query->get('elementType');
        $allowedTypes = ['asset', 'document', 'object'];
        $offset = (int)$request->get('start', 0);
        $limit = (int)$request->get('limit', 25);

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            $dependencies = $element->getDependencies();

            if ($element instanceof Model\Element\ElementInterface) {
                $dependenciesResult = Model\Element\Service::getRequiresDependenciesForFrontend($dependencies, $offset, $limit);

                $dependenciesResult['start'] = $offset;
                $dependenciesResult['limit'] = $limit;
                $dependenciesResult['total'] = $dependencies->getRequiresTotalCount();

                return $this->adminJson($dependenciesResult);
            }
        }

        return $this->adminJson(false);
    }

    /**
     * @Route("/element/get-required-by-dependencies", name="pimcore_admin_element_getrequiredbydependencies", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getRequiredByDependenciesAction(Request $request): JsonResponse
    {
        $id = $request->query->getInt('id');
        $type = $request->query->get('elementType');
        $allowedTypes = ['asset', 'document', 'object'];
        $offset = (int)$request->get('start', 0);
        $limit = (int)$request->get('limit', 25);

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            $dependencies = $element->getDependencies();

            if ($element instanceof Model\Element\ElementInterface) {
                $dependenciesResult = Model\Element\Service::getRequiredByDependenciesForFrontend($dependencies, $offset, $limit);

                $dependenciesResult['start'] = $offset;
                $dependenciesResult['limit'] = $limit;
                $dependenciesResult['total'] = $dependencies->getRequiredByTotalCount();

                return $this->adminJson($dependenciesResult);
            }
        }

        return $this->adminJson(false);
    }
}
