<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Http\Exception\ResponseException;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Webservice\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Stopwatch\Stopwatch;

abstract class AbstractRestController extends AdminController
{
    const ELEMENT_DOES_NOT_EXIST = -1;
    const TAG_DOES_NOT_EXIST = -1;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @var Service
     */
    protected $service;

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->service = $container->get('pimcore_admin.webservice.service');
    }

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
     * @param array|string $data
     * @return array
     */
    protected function createSuccessData($data = null)
    {
        return array_merge(['success' => true], $this->normalizeResponseData($data));
    }

    /**
     * @param array|string $data
     * @return array
     */
    protected function createErrorData($data = null)
    {
        return array_merge(['success' => false], $this->normalizeResponseData($data));
    }

    /**
     * @param array|string $data
     * @return array
     */
    protected function normalizeResponseData($data = null)
    {
        if (null === $data) {
            $data = [];
        } else if (is_string($data)) {
            $data = ['msg' => $data];
        }

        return $data;
    }

    /**
     * @param array|string $data
     * @param int|null $status
     *
     * @return JsonResponse
     */
    protected function createSuccessResponse($data = null, $status = Response::HTTP_OK)
    {
        return $this->json(
            $this->createSuccessData($data),
            $status
        );
    }

    /**
     * @param array|string $data
     * @param int|null $status
     *
     * @return JsonResponse
     */
    protected function createErrorResponse($data = null, $status = Response::HTTP_BAD_REQUEST)
    {
        return $this->json(
            $this->createErrorData($data),
            $status
        );
    }

    /**
     * Get decoded JSON request data
     *
     * @param Request $request
     * @return array
     *
     * @throws ResponseException
     */
    protected function getJsonData(Request $request)
    {
        $data  = null;
        $error = null;

        try {
            $data = $this->decodeJson($request->getContent());
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to decode JSON data for request {request}', [
                'request' => $request->getPathInfo()
            ]);

            $data  = null;
            $error = $e->getMessage();
        }

        if (!is_array($data)) {
            $message = 'Invalid data';
            if (\Pimcore::inDebugMode()) {
                $message .= ': ' . $error;
            }

            throw new ResponseException($this->createErrorResponse([
                'msg' => $message
            ]));
        }

        return $data;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->get('monolog.logger.pimcore_api');
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

    /**
     * @param $wsData
     * @param $data
     *
     * @return \Pimcore\Model\Webservice\Data
     */
    protected function map($wsData, $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $tmp = [];

                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        $object = new \stdClass();
                        $object = $this->map($object, $subvalue);

                        $tmp[$subkey] = $object;
                    } else {
                        $tmp[$subkey] = $subvalue;
                    }
                }
                $value = $tmp;

            }
            $wsData->$key = $value;
        }

        if ($wsData instanceof \Pimcore\Model\Webservice\Data\Object) {
            /** @var \Pimcore\Model\Webservice\Data\Object key */
            $wsData->key = \Pimcore\Model\Element\Service::getValidKey($wsData->key, "object");
        } elseif ($wsData instanceof \Pimcore\Model\Webservice\Data\Document) {
            /** @var \Pimcore\Model\Webservice\Data\Document key */
            $wsData->key = \Pimcore\Model\Element\Service::getValidKey($wsData->key, "document");
        } elseif ($wsData instanceof \Pimcore\Model\Webservice\Data\Asset) {
            /** @var \Pimcore\Model\Webservice\Data\Asset $wsData */
            $wsData->filename = \Pimcore\Model\Element\Service::getValidKey($wsData->filename, "asset");
        }

        return $wsData;
    }

    /**
     * @param $class
     * @param $data
     *
     * @return \Pimcore\Model\Webservice\Data
     */
    protected function fillWebserviceData($class, $data)
    {
        $wsData = new $class();

        return self::map($wsData, $data);
    }
}
