<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\ControllerApi;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Http\Exception\ResponseException;
use Pimcore\Model\Element\AbstractElement;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

abstract class AbstractApiController extends AdminController
{
    const ELEMENT_DOES_NOT_EXIST = -1;
    const TAG_DOES_NOT_EXIST = -1;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @inheritDoc
     */
    public function needsSessionDoubleAuthenticationCheck()
    {
        // do not double-check session as api key auth is possible
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
            throw new ResponseException($this->createErrorResponse([
                'msg' => sprintf('Not allowed: permission %s is needed', $permission)
            ]));
        }
    }

    /**
     * @param AbstractElement $element
     * @param string $type
     *
     * @throws ResponseException
     */
    protected function checkElementPermission(AbstractElement $element, $type)
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
            $this->get('monolog.logger.security')->error(
                'User {user} attempted to access {permission} on {elementType} {elementId}, but has no permission to do so', [
                    'user'        => $this->getUser()->getName(),
                    'permission'  => $permission,
                    'elementType' => $element->getType(),
                    'elementId'   => $element->getId(),
                ]
            );

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

    /**
     * @return Stopwatch
     */
    protected function getStopwatch()
    {
        if (null === $this->stopwatch) {
            if ($this->container->has('debug.stopwatch')) {
                $this->stopwatch = $this->container->get('debug.stopwatch');
            } else {
                $this->stopwatch = new Stopwatch();
            }
        }

        return $this->stopwatch;
    }

    /**
     * @return Stopwatch
     */
    protected function startProfiling()
    {
        $stopwatch = $this->getStopwatch();
        $stopwatch->openSection();

        return $stopwatch;
    }

    /**
     * @param string $sectionName
     * @return array
     */
    protected function getProfilingData($sectionName)
    {
        $stopwatch = $this->getStopwatch();
        $stopwatch->stopSection($sectionName);

        $data = [];
        foreach ($this->getStopwatch()->getSectionEvents($sectionName) as $name => $event) {
            if ($name === '__section__') {
                $name = 'total';
            }

            $data[$name] = $event->getDuration();
        }

        return $data;
    }
}
