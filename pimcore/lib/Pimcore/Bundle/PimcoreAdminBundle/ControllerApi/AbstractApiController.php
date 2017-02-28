<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\ControllerApi;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Http\Exception\ResponseException;
use Pimcore\Model\Element\AbstractElement;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class AbstractApiController extends AdminController
{
    const ELEMENT_DOES_NOT_EXIST = -1;
    const TAG_DOES_NOT_EXIST = -1;

    /**
     * @inheritDoc
     */
    public function needsSessionDoubleAuthenticationCheck()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function checkPermission($permission)
    {
        try {
            parent::checkPermission($permission);
        } catch (AccessDeniedHttpException $ex) {
            throw new AccessDeniedHttpException($this->encodeJson([
                "success" => false,
                "msg" => "not allowed"
            ]));
        }
    }

    /**
     * @param AbstractElement $element
     * @param string $type
     *
     * @throws ResponseException
     */
    protected function checkElementPermission($element, $type)
    {
        $map = [
            'get'    => 'view',
            'delete' => 'delete',
            'update' => 'publish',
            'create' => 'create'
        ];

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid permission type: %s', $type));
        }

        $permission = $map[$type];
        if (!$element->isAllowed($permission)) {
            throw new ResponseException($this->createErrorResponse([
                'msg' => sprintf('Not allowed: permission %s is needed', $permission)
            ]));
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function createSuccessData(array $data = [])
    {
        return array_merge(['success' => true], $data);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function createErrorData(array $data = [])
    {
        return array_merge(['success' => false], $data);
    }

    /**
     * @param array $data
     * @param int|null $status
     *
     * @return JsonResponse
     */
    protected function createSuccessResponse(array $data = [], $status = Response::HTTP_OK)
    {
        return new JsonResponse(
            $this->createSuccessData($data),
            $status
        );
    }

    /**
     * @param array $data
     * @param int|null $status
     *
     * @return JsonResponse
     */
    protected function createErrorResponse(array $data = [], $status = Response::HTTP_BAD_REQUEST)
    {
        return new JsonResponse(
            $this->createErrorData($data),
            $status
        );
    }
}
