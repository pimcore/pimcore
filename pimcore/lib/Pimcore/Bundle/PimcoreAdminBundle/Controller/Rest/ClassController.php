<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest;

use Pimcore\Bundle\PimcoreBundle\Http\Exception\ResponseException;
use Pimcore\Model\Object;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\Webservice\Service;

/**
 * Contains actions to gather information about the API. The /info/user endpoint
 * is used in tests.
 */
class ClassController extends AbstractRestController
{
    /**
     * @var Service
     */
    protected $service;

    public function __construct()
    {
        $this->service = new Service();
    }

    /**
     * @Route("/classes")
     */
    public function classesAction()
    {
        $this->checkPermission('classes');

        $list    = new Object\ClassDefinition\Listing();
        $classes = $list->load();

        $result = [];

        foreach ($classes as $class) {
            $item     = [
                'id'   => $class->getId(),
                'name' => $class->getName()
            ];
            
            $result[] = $item;
        }

        return $this->createSuccessResponse([
            'data' => $result
        ]);
    }

    /**
     * @Route("/class/id/{id}", requirements={"id": "\d+"})
     *
     * end point for the class definition
     *
     *  GET http://[YOUR-DOMAIN]/webservice/rest/class/id/1281?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     */
    public function classAction($id)
    {
        $this->checkPermission('classes');

        $class = $this->service->getClassById($id);
        if (!$class) {
            throw new ResponseException($this->createErrorResponse(
                Response::$statusTexts[Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            ));
        }

        return $this->createSuccessResponse([
            'data' => $class
        ]);
    }
}
