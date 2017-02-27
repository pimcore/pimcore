<?php
namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;


use Pimcore\Model\Element\Service;
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
