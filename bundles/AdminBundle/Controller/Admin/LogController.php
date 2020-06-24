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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Doctrine\DBAL\Types\Type;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Helper\QueryParams;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Db;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AdminController implements EventedControllerInterface
{
    /**
     * @inheritDoc
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$this->getAdminUser()->isAllowed('application_logging')) {
            throw new AccessDeniedHttpException("Permission denied, user needs 'application_logging' permission.");
        }
    }

    /**
     * @inheritDoc
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
    }

    /**
     * @Route("/log/show", name="pimcore_admin_log_show", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function showAction(Request $request, Db\ConnectionInterface $db)
    {
        $qb = $db->createQueryBuilder();
        $qb
            ->select('*')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->setFirstResult($request->get('start', 0))
            ->setMaxResults($request->get('limit', 50));

        $sortingSettings = QueryParams::extractSortingSettings(array_merge(
            $request->request->all(),
            $request->query->all()
        ));

        if ($sortingSettings['orderKey']) {
            $qb->orderBy($sortingSettings['orderKey'], $sortingSettings['order']);
        } else {
            $qb->orderBy('id', 'DESC');
        }

        $priority = $request->get('priority');
        if ($priority !== '-1' && ($priority == '0' || $priority)) {
            $levels = [];

            // add every level until the filtered one
            foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level) {
                $levels[] = $level;

                if ($priority === $level) {
                    break;
                }
            }

            $qb->andWhere($qb->expr()->in('priority', ':priority'));
            $qb->setParameter('priority', $levels, Db\Connection::PARAM_STR_ARRAY);
        }

        if ($fromDate = $this->parseDateObject($request->get('fromDate'), $request->get('fromTime'))) {
            $qb->andWhere('timestamp > :fromDate');
            $qb->setParameter('fromDate', $fromDate, Type::DATETIME);
        }

        if ($toDate = $this->parseDateObject($request->get('toDate'), $request->get('toTime'))) {
            $qb->andWhere('timestamp <= :toDate');
            $qb->setParameter('toDate', $toDate, Type::DATETIME);
        }

        if (!empty($component = $request->get('component'))) {
            $qb->andWhere('component = ' . $qb->createNamedParameter($component));
        }

        if (!empty($relatedObject = $request->get('relatedobject'))) {
            $qb->andWhere('relatedobject = ' . $qb->createNamedParameter($relatedObject));
        }

        if (!empty($message = $request->get('message'))) {
            $qb->andWhere('message LIKE ' . $qb->createNamedParameter('%' . $message . '%'));
        }

        if (!empty($pid = $request->get('pid'))) {
            $qb->andWhere('pid LIKE ' . $qb->createNamedParameter('%' . $pid . '%'));
        }

        $totalQb = clone $qb;
        $totalQb->setMaxResults(null)
            ->setFirstResult(0)
            ->select('COUNT(id) as count');
        $total = $totalQb->execute()->fetch();
        $total = (int) $total['count'];

        $stmt = $qb->execute();
        $result = $stmt->fetchAll();

        $logEntries = [];
        foreach ($result as $row) {
            $fileobject = null;
            if ($row['fileobject']) {
                $fileobject = str_replace(PIMCORE_PROJECT_ROOT, '', $row['fileobject']);
            }

            $logEntry = [
                'id' => $row['id'],
                'pid' => $row['pid'],
                'message' => $row['message'],
                'timestamp' => $row['timestamp'],
                'priority' => $this->getPriorityName($row['priority']),
                'fileobject' => $fileobject,
                'relatedobject' => $row['relatedobject'],
                'relatedobjecttype' => $row['relatedobjecttype'],
                'component' => $row['component'],
                'source' => $row['source'],
            ];

            $logEntries[] = $logEntry;
        }

        return $this->adminJson([
            'p_totalCount' => $total,
            'p_results' => $logEntries,
        ]);
    }

    /**
     * @param string|null $date
     * @param string|null $time
     *
     * @return \DateTime|null
     */
    private function parseDateObject($date = null, $time = null)
    {
        if (empty($date)) {
            return null;
        }

        $pattern = '/^(?P<date>\d{4}\-\d{2}\-\d{2})T(?P<time>\d{2}:\d{2}:\d{2})$/';

        $dateTime = null;
        if (preg_match($pattern, $date, $dateMatches)) {
            if (!empty($time) && preg_match($pattern, $time, $timeMatches)) {
                $dateTime = new \DateTime(sprintf('%sT%s', $dateMatches['date'], $timeMatches['time']));
            } else {
                $dateTime = new \DateTime($date);
            }
        }

        return $dateTime;
    }

    /**
     * @param int $priority
     *
     * @return string
     */
    private function getPriorityName($priority)
    {
        $p = ApplicationLoggerDb::getPriorities();

        return $p[$priority];
    }

    /**
     * @Route("/log/priority-json", name="pimcore_admin_log_priorityjson", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function priorityJsonAction(Request $request)
    {
        $priorities[] = ['key' => '-1', 'value' => '-'];
        foreach (ApplicationLoggerDb::getPriorities() as $key => $p) {
            $priorities[] = ['key' => $key, 'value' => $p];
        }

        return $this->adminJson(['priorities' => $priorities]);
    }

    /**
     * @Route("/log/component-json", name="pimcore_admin_log_componentjson", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function componentJsonAction(Request $request)
    {
        $components[] = ['key' => '', 'value' => '-'];
        foreach (ApplicationLoggerDb::getComponents() as $p) {
            $components[] = ['key' => $p, 'value' => $p];
        }

        return $this->adminJson(['components' => $components]);
    }

    /**
     * @Route("/log/show-file-object", name="pimcore_admin_log_showfileobject", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function showFileObjectAction(Request $request)
    {
        $filePath = $request->get('filePath');
        $filePath = PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . $filePath;
        $filePath = realpath($filePath);
        $fileObjectPath = realpath(PIMCORE_LOG_FILEOBJECT_DIRECTORY);

        if (!preg_match('@^' . $fileObjectPath . '@', $filePath)) {
            throw new AccessDeniedHttpException('Accessing file out of scope');
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');

        if (file_exists($filePath)) {
            $response->setContent(file_get_contents($filePath));
            if (strpos($response->getContent(), '</html>') > 0 || strpos($response->getContent(), '</pre>') > 0) {
                $response->headers->set('Content-Type', 'text/html');
            }
        } else {
            $response->setContent('Path `' . $filePath . '` not found.');
            $response->setStatusCode(404);
        }

        return $response;
    }
}
