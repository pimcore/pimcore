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

namespace Pimcore\Bundle\AdminBundle\Controller\Rest;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Event\Webservice\FilterEvent;
use Pimcore\Event\WebserviceEvents;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Webservice\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @deprecated
 */
abstract class AbstractRestController extends AdminController
{
    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @var Service
     */
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
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
                'msg' => sprintf('Not allowed: permission %s is needed', $permission),
            ]));
        }
    }

    /**
     * @param array|string $data
     * @param bool         $wrapInDataProperty
     *
     * @return array
     */
    protected function createSuccessData($data = null, $wrapInDataProperty = true)
    {
        if ($wrapInDataProperty) {
            $data = [
                'data' => $data,
            ];
        }

        return array_merge(['success' => true], $this->normalizeResponseData($data));
    }

    /**
     * @param array|string $data
     *
     * @return array
     */
    protected function createErrorData($data = null)
    {
        return array_merge(['success' => false], $this->normalizeResponseData($data));
    }

    /**
     * @param array|string $data
     *
     * @return array
     */
    protected function normalizeResponseData($data = null)
    {
        if (null === $data) {
            $data = [];
        } elseif (is_string($data)) {
            $data = ['msg' => $data];
        }

        return $data;
    }

    /**
     * @param array|string $data
     * @param bool         $wrapInDataProperty
     * @param int|null     $status
     *
     * @return JsonResponse
     */
    protected function createSuccessResponse($data = null, $wrapInDataProperty = true, $status = Response::HTTP_OK)
    {
        return $this->adminJson(
            $this->createSuccessData($data, $wrapInDataProperty),
            $status
        );
    }

    /**
     * @param array $data
     * @param int   $status
     *
     * @return JsonResponse
     */
    protected function createCollectionSuccessResponse(array $data = [], $status = Response::HTTP_OK)
    {
        return $this->createSuccessResponse([
            'total' => count($data),
            'data' => $data,
        ], false, $status);
    }

    /**
     * @param array|string $data
     * @param int|null $status
     *
     * @return JsonResponse
     */
    protected function createErrorResponse($data = null, $status = Response::HTTP_BAD_REQUEST)
    {
        return $this->adminJson(
            $this->createErrorData($data),
            $status
        );
    }

    /**
     * @inheritDoc
     */
    protected function createNotFoundResponseException($message = null, \Exception $previous = null)
    {
        return new ResponseException($this->createErrorResponse(
            $message ?: Response::$statusTexts[Response::HTTP_NOT_FOUND],
            Response::HTTP_NOT_FOUND
        ), $previous);
    }

    /**
     * Get decoded JSON request data
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws ResponseException
     */
    protected function getJsonData(Request $request)
    {
        $data = null;
        $error = null;

        try {
            $data = $this->decodeJson($request->getContent());
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to decode JSON data for request {request}', [
                'request' => $request->getPathInfo(),
            ]);

            $data = null;
            $error = $e->getMessage();
        }

        if (!is_array($data)) {
            $message = 'Invalid data';
            if (\Pimcore::inDebugMode()) {
                $message .= ': ' . $error;
            }

            throw new ResponseException($this->createErrorResponse([
                'msg' => $message,
            ]));
        }

        return $data;
    }

    /**
     * Get ID either as parameter or from request
     *
     * @param Request $request
     * @param int|null $id
     *
     * @return mixed|null
     *
     * @throws ResponseException
     */
    protected function resolveId(Request $request, $id = null)
    {
        if (null !== $id) {
            return $id;
        }

        if ($id = $request->get('id')) {
            return $id;
        }

        throw new ResponseException($this->createErrorResponse('Missing ID'));
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
     *
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
     * @param string $condition
     *
     * @throws \Exception
     */
    protected function checkCondition($condition)
    {
        if (strpos($condition, ';') !== false) {
            throw new \Exception('Semicolon is not allowed as part of the condition');
        }
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return string|null
     */
    protected function buildCondition(Request $request)
    {
        $q = trim($request->get('q'));
        if (!$q) {
            return null;
        }
        $q = json_decode($q, false);
        if (!$q) {
            throw new \Exception('failed to parse filter');
        }

        $condition = Helper::buildSqlCondition($q);

        return $condition;
    }

    /**
     * @param Request $request
     * @param FilterEvent $eventData
     */
    public function dispatchBeforeLoadEvent(Request $request, FilterEvent $eventData)
    {
        if ($request->get('condition')) {
            \Pimcore::getEventDispatcher()->dispatch(WebserviceEvents::BEFORE_LIST_LOAD, $eventData);
            if (!$eventData->isConditionDirty()) {
                throw new \Exception('the condition parameter is not supported anymore');
            }
        }
    }
}
