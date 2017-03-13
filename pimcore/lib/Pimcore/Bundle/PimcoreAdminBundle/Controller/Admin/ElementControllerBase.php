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

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Model\Element\Service;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ElementControllerBase extends AdminController
{

    /**
     * @param $element
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        return [];
    }

    /**
     * @Route("/tree-get-root")
     * @param Request $request
     * @return JsonResponse
     */
    public function treeGetRootAction(Request $request)
    {
        $type = $request->get("elementType");
        $allowedTypes = ["asset", "document", "object"];

        $id = 1;
        if ($request->get("id")) {
            $id = intval($request->get("id"));
        }

        if (in_array($type, $allowedTypes)) {
            $root = Service::getElementById($type, $id);
            if ($root->isAllowed("list")) {
                return $this->json($this->getTreeNodeConfig($root));
            }
        }

        return $this->json(["success" => false, "message" => "missing_permission"]);
    }
}
