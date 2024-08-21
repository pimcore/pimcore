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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Controller;

use Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Tool\Storage;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
class LogController extends UserAwareController implements KernelControllerEventInterface
{
    use JsonHelperTrait;

    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$this->getPimcoreUser()->isAllowed('application_logging')) {
            throw new AccessDeniedHttpException("Permission denied, user needs 'application_logging' permission.");
        }
    }

    /**
     * @Route("/log/show", name="pimcore_admin_bundle_applicationlogger_log_show", methods={"GET", "POST"})
     *
     *
     */
    public function showAction(Request $request, Connection $db): JsonResponse
    {
        $this->checkPermission('application_logging');

        $qb = $db->createQueryBuilder();
        $qb
            ->select('*')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->setFirstResult($request->get('start', 0))
            ->setMaxResults($request->get('limit', 50));

        $qb->orderBy('id', 'DESC');

        if (class_exists(\Pimcore\Bundle\AdminBundle\Helper\QueryParams::class)) {
            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge(
                $request->request->all(),
                $request->query->all()
            ));

            if ($sortingSettings['orderKey']) {
                $qb->orderBy($db->quoteIdentifier($sortingSettings['orderKey']), $sortingSettings['order']);
            }
        }

        $priority = $request->get('priority');
        if (!empty($priority)) {
            $qb->andWhere($qb->expr()->eq('priority', ':priority'));
            $qb->setParameter('priority', $priority);
        }

        if ($fromDate = $this->parseDateObject($request->get('fromDate'), $request->get('fromTime'))) {
            $qb->andWhere('timestamp > :fromDate');
            $qb->setParameter('fromDate', $fromDate, Types::DATETIME_MUTABLE);
        }

        if ($toDate = $this->parseDateObject($request->get('toDate'), $request->get('toTime'))) {
            $qb->andWhere('timestamp <= :toDate');
            $qb->setParameter('toDate', $toDate, Types::DATETIME_MUTABLE);
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
        $total = $totalQb->executeQuery()->fetchAssociative();
        $total = (int) $total['count'];

        $stmt = $qb->executeQuery();
        $result = $stmt->fetchAllAssociative();

        $logEntries = [];
        foreach ($result as $row) {
            $fileobject = null;
            if ($row['fileobject']) {
                $fileobject = str_replace(PIMCORE_PROJECT_ROOT, '', $row['fileobject']);
            }

            $carbonTs = new Carbon($row['timestamp'], 'UTC');
            $logEntry = [
                'id' => $row['id'],
                'pid' => $row['pid'],
                'message' => $row['message'],
                'date' => $row['timestamp'],
                'timestamp' => $carbonTs->getTimestamp(),
                'priority' => $row['priority'],
                'fileobject' => $fileobject,
                'relatedobject' => $row['relatedobject'],
                'relatedobjecttype' => $row['relatedobjecttype'],
                'component' => $row['component'],
                'source' => $row['source'],
            ];

            $logEntries[] = $logEntry;
        }

        return $this->jsonResponse([
            'p_totalCount' => $total,
            'p_results' => $logEntries,
        ]);
    }

    private function parseDateObject(?string $date, ?string $time): ?DateTime
    {
        if (empty($date)) {
            return null;
        }

        $pattern = '/^(?P<date>\d{4}\-\d{2}\-\d{2})T(?P<time>\d{2}:\d{2}:\d{2})$/';

        $dateTime = null;
        if (preg_match($pattern, $date, $dateMatches)) {
            if (!empty($time) && preg_match($pattern, $time, $timeMatches)) {
                $dateTime = new DateTime(sprintf('%sT%s', $dateMatches['date'], $timeMatches['time']));
            } else {
                $dateTime = new DateTime($date);
            }
        }

        return $dateTime;
    }

    /**
     * @Route("/log/priority-json", name="pimcore_admin_bundle_applicationlogger_log_priorityjson", methods={"GET"})
     *
     *
     */
    public function priorityJsonAction(Request $request): JsonResponse
    {
        $this->checkPermission('application_logging');

        $priorities[] = ['key' => '', 'value' => '-'];
        foreach (ApplicationLoggerDb::getPriorities() as $key => $p) {
            $priorities[] = ['key' => $key, 'value' => $p];
        }

        return $this->jsonResponse(['priorities' => $priorities]);
    }

    /**
     * @Route("/log/component-json", name="pimcore_admin_bundle_applicationlogger_log_componentjson", methods={"GET"})
     *
     *
     */
    public function componentJsonAction(Request $request): JsonResponse
    {
        $this->checkPermission('application_logging');

        $components[] = ['key' => '', 'value' => '-'];
        foreach (ApplicationLoggerDb::getComponents() as $p) {
            $components[] = ['key' => $p, 'value' => $p];
        }

        return $this->jsonResponse(['components' => $components]);
    }

    /**
     * @Route("/log/show-file-object", name="pimcore_admin_bundle_applicationlogger_log_showfileobject", methods={"GET"})
     */
    public function showFileObjectAction(Request $request): StreamedResponse
    {
        $this->checkPermission('application_logging');

        $filePath = $request->get('filePath');
        $storage = Storage::get('application_log');

        if ($storage->fileExists($filePath)) {
            $fileData = $storage->readStream($filePath);
            $response = new StreamedResponse(
                static function () use ($fileData) {
                    echo stream_get_contents($fileData);
                }
            );
            $response->headers->set('Content-Type', 'text/plain');

            return $response;
        }

        throw new FileNotFoundException($filePath);
    }
}
