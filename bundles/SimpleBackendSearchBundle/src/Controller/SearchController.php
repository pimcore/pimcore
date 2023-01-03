<?php
declare(strict_types=1);

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Controller;

use Pimcore\Config;
use Pimcore\Event\AdminEvents;
use Pimcore\Model\Element;
use Pimcore\Model\DataObject;
use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\GenericEvent;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Pimcore\Bundle\AdminBundle\Controller\Traits\AdminStyleTrait;

/**
 * @Route("/search")
 *
 * @internal
 */
class SearchController extends AdminController
{
    use AdminStyleTrait;

    /**
     * @Route("/quicksearch-get-by-id", name="pimcore_search_quicksearch_by_id", methods={"GET"})
     *
     * @param Request $request
     * @param Config $config
     *
     * @return JsonResponse
     */
    public function quicksearchByIdAction(Request $request, Config $config): JsonResponse
    {
        $type = $request->get('type');
        $id = $request->get('id');
        $db = \Pimcore\Db::get();
        $searcherList = new Data\Listing();

        $searcherList->addConditionParam('id = :id', ['id' => $id]);
        $searcherList->addConditionParam('maintype = :type', ['type' => $type]);
        $searcherList->setLimit(1);

        $hits = $searcherList->load();

        //There will always be one result in hits but load returns array.
        $data = [];
        foreach ($hits as $hit) {
            $element = Element\Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element->isAllowed('list')) {
                $data = [
                    'id' => $element->getId(),
                    'type' => $hit->getId()->getType(),
                    'subtype' => $element->getType(),
                    'className' => ($element instanceof DataObject\Concrete) ? $element->getClassName() : '',
                    'fullpath' => htmlspecialchars($element->getRealFullPath()),
                    'fullpathList' => htmlspecialchars($this->shortenPath($element->getRealFullPath())),
                    'iconCls' => 'pimcore_icon_asset_default',
                ];

                $this->addAdminStyle($element, ElementAdminStyleEvent::CONTEXT_SEARCH, $data);

                $validLanguages = \Pimcore\Tool::getValidLanguages();

                $data['preview'] = $this->renderView(
                    '@SimpleBackendSearchBundle/quicksearch/' . $hit->getId()->getType() . '.html.twig', [
                        'element' => $element,
                        'iconCls' => $data['iconCls'],
                        'config' => $config,
                        'validLanguages' => $validLanguages,
                    ]
                );
            }
        }

        return $this->adminJson($data);
    }

    /**
     * @Route("/quicksearch", name="pimcore_search_quicksearch", methods={"GET"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function quicksearchAction(Request $request, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        $query = $this->filterQueryParam($request->get('query', ''));
        if (!preg_match('/[\+\-\*"]/', $query)) {
            // check for a boolean operator (which was not filtered by filterQueryParam()),
            // if present, do not add asterisk at the end of the query
            $query = $query . '*';
        }

        $db = \Pimcore\Db::get();
        $searcherList = new Data\Listing();

        $conditionParts = [];

        $conditionParts[] = $this->getPermittedPaths();

        $matchCondition = '( MATCH (`data`,`properties`) AGAINST (' . $db->quote($query) . ' IN BOOLEAN MODE) )';
        $conditionParts[] = '(' . $matchCondition . " AND `type` != 'folder') ";

        $queryCondition = implode(' AND ', $conditionParts);

        $searcherList->setCondition($queryCondition);
        $searcherList->setLimit(50);
        $searcherList->setOrderKey($matchCondition, false);
        $searcherList->setOrder('DESC');

        $beforeListLoadEvent = new GenericEvent($this, [
            'list' => $searcherList,
            'query' => $query,
        ]);
        $eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::QUICKSEARCH_LIST_BEFORE_LIST_LOAD);
        $searcherList = $beforeListLoadEvent->getArgument('list');

        $hits = $searcherList->load();

        $elements = [];
        foreach ($hits as $hit) {
            $element = Element\Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element->isAllowed('list')) {
                $data = [
                    'id' => $element->getId(),
                    'type' => $hit->getId()->getType(),
                    'subtype' => $element->getType(),
                    'className' => ($element instanceof DataObject\Concrete) ? $element->getClassName() : '',
                    'fullpathList' => htmlspecialchars($this->shortenPath($element->getRealFullPath())),
                ];

                $this->addAdminStyle($element, ElementAdminStyleEvent::CONTEXT_SEARCH, $data);

                $elements[] = $data;
            }
        }

        $afterListLoadEvent = new GenericEvent($this, [
            'list' => $elements,
            'context' => $query,
        ]);
        $eventDispatcher->dispatch($afterListLoadEvent, AdminEvents::QUICKSEARCH_LIST_AFTER_LIST_LOAD);
        $elements = $afterListLoadEvent->getArgument('list');

        $result = ['data' => $elements, 'success' => true];

        return $this->adminJson($result);
    }

    protected function filterQueryParam(string $query): string
    {
        if ($query == '*') {
            $query = '';
        }

        $query = str_replace('&quot;', '"', $query);
        $query = str_replace('%', '*', $query);
        $query = str_replace('@', '#', $query);
        $query = preg_replace("@([^ ])\-@", '$1 ', $query);

        $query = str_replace(['<', '>', '(', ')', '~'], ' ', $query);

        // it is not allowed to have * behind another *
        $query = preg_replace('#[*]+#', '*', $query);

        // no boolean operators at the end of the query
        $query = rtrim($query, '+- ');

        return $query;
    }

    /**
     * @param array $types
     *
     * @return string
     *
     *@internal
     *
     */
    protected function getPermittedPaths(array $types = ['asset', 'document', 'object']): string
    {
        $user = $this->getAdminUser();
        $db = \Pimcore\Db::get();

        $allowedTypes = [];

        foreach ($types as $type) {
            if ($user->isAllowed($type . 's')) { //the permissions are just plural
                $elementPaths = Element\Service::findForbiddenPaths($type, $user);

                $forbiddenPathSql = [];
                $allowedPathSql = [];
                foreach ($elementPaths['forbidden'] as $forbiddenPath => $allowedPaths) {
                    $exceptions = '';
                    $folderSuffix = '';
                    if ($allowedPaths) {
                        $exceptionsConcat = implode("%' OR fullpath LIKE '", $allowedPaths);
                        $exceptions = " OR (fullpath LIKE '" . $exceptionsConcat . "%')";
                        $folderSuffix = '/'; //if allowed children are found, the current folder is listable but its content is still blocked, can easily done by adding a trailing slash
                    }
                    $forbiddenPathSql[] = ' (fullpath NOT LIKE ' . $db->quote($forbiddenPath . $folderSuffix . '%') . $exceptions . ') ';
                }
                foreach ($elementPaths['allowed'] as $allowedPaths) {
                    $allowedPathSql[] = ' fullpath LIKE ' . $db->quote($allowedPaths  . '%');
                }

                // this is to avoid query error when implode is empty.
                // the result would be like `(maintype = type AND ((path1 OR path2) AND (not_path3 AND not_path4)))`
                $forbiddenAndAllowedSql = '(maintype = \'' . $type . '\'';

                if ($allowedPathSql || $forbiddenPathSql) {
                    $forbiddenAndAllowedSql .= ' AND (';
                    $forbiddenAndAllowedSql .= $allowedPathSql ? '( ' . implode(' OR ', $allowedPathSql) . ' )' : '';

                    if ($forbiddenPathSql) {
                        //if $allowedPathSql "implosion" is present, we need `AND` in between
                        $forbiddenAndAllowedSql .= $allowedPathSql ? ' AND ' : '';
                        $forbiddenAndAllowedSql .= implode(' AND ', $forbiddenPathSql);
                    }
                    $forbiddenAndAllowedSql .= ' )';
                }

                $forbiddenAndAllowedSql.= ' )';

                $allowedTypes[] = $forbiddenAndAllowedSql;
            }
        }

        //if allowedTypes is still empty after getting the workspaces, it means that there are no any master permissions set
        // by setting a `false` condition in the query makes sure that nothing would be displayed.
        if (!$allowedTypes) {
            $allowedTypes = ['false'];
        }

        return '('.implode(' OR ', $allowedTypes) .')';
    }

    protected function shortenPath(string $path): string
    {
        $parts = explode('/', trim($path, '/'));
        $count = count($parts) - 1;

        for ($i = $count; ; $i--) {
            $shortPath = '/' . implode('/', array_unique($parts));
            if ($i === 0 || strlen($shortPath) <= 50) {
                break;
            }
            array_splice($parts, $i - 1, 1, '…');
        }

        if (mb_strlen($shortPath) > 50) {
            $shortPath = mb_substr($shortPath, 0, 49) . '…';
        }

        return $shortPath;
    }
}
