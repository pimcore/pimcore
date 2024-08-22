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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Controller;

use Doctrine\DBAL\Exception\SyntaxErrorException;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Bundle\AdminBundle\Event\ElementAdminStyleEvent;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Bundle\AdminBundle\Helper\QueryParams;
use Pimcore\Bundle\AdminBundle\Service\GridData;
use Pimcore\Bundle\SimpleBackendSearchBundle\Event\AdminSearchEvents;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use Pimcore\Config;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Db\Helper;
use Pimcore\Extension\Bundle\Exception\AdminClassicBundleNotFoundException;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Element\AdminStyle;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/search")
 *
 * @internal
 */
class SearchController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @Route("/find", name="pimcore_bundle_search_search_find", methods={"GET", "POST"})
     *
     * @todo: $conditionTypeParts could be undefined
     *
     * @todo: $conditionSubtypeParts could be undefined
     *
     * @todo: $conditionClassnameParts could be undefined
     *
     * @todo: $data could be undefined
     */
    public function findAction(Request $request, EventDispatcherInterface $eventDispatcher, GridHelperService $gridHelperService): JsonResponse
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $requestedLanguage = $allParams['language'] ?? null;
        if ($requestedLanguage) {
            if ($requestedLanguage != 'default') {
                $request->setLocale($requestedLanguage);
            }
        }

        $filterPrepareEvent = new GenericEvent($this, [
            'requestParams' => $allParams,
        ]);
        $eventDispatcher->dispatch($filterPrepareEvent, AdminSearchEvents::SEARCH_LIST_BEFORE_FILTER_PREPARE);

        $allParams = $filterPrepareEvent->getArgument('requestParams');

        $query = $this->filterQueryParam($allParams['query'] ?? '');

        $types = explode(',', preg_replace('/[^a-z,]/i', '', $allParams['type'] ?? ''));
        $subtypes = explode(',', preg_replace('/[^a-z,]/i', '', $allParams['subtype'] ?? ''));
        $classnames = explode(',', preg_replace('/[^a-z0-9_,]/i', '', $allParams['class'] ?? ''));

        $offset = (int)$allParams['start'];
        $limit = (int)$allParams['limit'];

        $offset = $offset ? $offset : 0;
        $limit = $limit ? $limit : 50;

        $searcherList = new Data\Listing();
        $conditionParts = [];
        $db = \Pimcore\Db::get();

        $conditionParts[] = $this->getPermittedPaths($types);

        $queryCondition = '';
        if (!empty($query)) {
            $queryCondition = '( MATCH (`data`,`properties`) AGAINST (' . $db->quote($query) . ' IN BOOLEAN MODE) )';

            // the following should be done with an exact-search now "ID", because the Element-ID is now in the fulltext index
            // if the query is numeric the user might want to search by id
            //if(is_numeric($query)) {
            //$queryCondition = "(" . $queryCondition . " OR id = " . $db->quote($query) ." )";
            //}

            $conditionParts[] = $queryCondition;
        }

        //For objects - handling of bricks
        $fields = [];
        $bricks = [];
        if (!empty($allParams['fields'])) {
            $fields = $allParams['fields'];
            //remove sql comments
            $fields = str_replace('--', '', $fields);

            foreach ($fields as $f) {
                $parts = explode('~', $f);
                if (str_starts_with($f, '~')) {
                    //                    $type = $parts[1];
                    //                    $field = $parts[2];
                    //                    $keyid = $parts[3];
                    // key value, ignore for now
                } elseif (count($parts) > 1) {
                    $bricks[$parts[0]] = $parts[0];
                }
            }
        }

        // filtering for objects
        if (!empty($allParams['filter']) && !empty($allParams['class'])) {
            $class = DataObject\ClassDefinition::getByName($allParams['class']);
            $localizedFields = $class->getFieldDefinition('localizedfields');

            // add Localized Fields filtering
            $params = $this->decodeJson($allParams['filter']);
            $unlocalizedFieldsFilters = [];
            $localizedFieldsFilters = [];

            foreach ($params as $paramConditionObject) {
                //this loop divides filter parameters to localized and unlocalized groups
                if (in_array($paramConditionObject['property'], DataObject\Service::getSystemFields())) {
                    $unlocalizedFieldsFilters[] = $paramConditionObject;
                } elseif ($localizedFields instanceof Localizedfields && $localizedFields->getFieldDefinition($paramConditionObject['property'])) {
                    $localizedFieldsFilters[] = $paramConditionObject;
                } elseif ($class->getFieldDefinition($paramConditionObject['property'])) {
                    $unlocalizedFieldsFilters[] = $paramConditionObject;
                }
            }

            //get filter condition only when filters array is not empty

            //string statements for divided filters
            $conditionFilters = count($unlocalizedFieldsFilters)
                ? $gridHelperService->getFilterCondition($this->encodeJson($unlocalizedFieldsFilters), $class)
                : null;
            $localizedConditionFilters = count($localizedFieldsFilters)
                ? $gridHelperService->getFilterCondition($this->encodeJson($localizedFieldsFilters), $class)
                : null;

            $join = '';
            $localizedJoin = '';
            foreach ($bricks as $ob) {
                $join .= ' LEFT JOIN object_brick_query_' . $ob . '_' . $class->getId();
                $join .= ' `' . $ob . '`';

                if ($localizedConditionFilters) {
                    $localizedJoin = $join . ' ON `' . $ob . '`.id = `object_localized_data_' . $class->getId() . '`.ooo_id';
                }

                $join .= ' ON `' . $ob . '`.id = `object_' . $class->getId() . '`.id';
            }

            if (null !== $conditionFilters) {
                //add condition query for non localised fields
                $conditionParts[] = '( id IN (SELECT `object_' . $class->getId() . '`.id FROM object_' . $class->getId()
                    . $join . ' WHERE ' . $conditionFilters . ') )';
            }

            if (null !== $localizedConditionFilters) {
                //add condition query for localised fields
                $conditionParts[] = '( id IN (SELECT `object_localized_data_' . $class->getId()
                    . '`.ooo_id FROM object_localized_data_' . $class->getId() . $localizedJoin . ' WHERE '
                    . $localizedConditionFilters . ' GROUP BY ooo_id ' . ') )';
            }
        }

        if ($types[0]) {
            $conditionTypeParts = [];
            foreach ($types as $type) {
                $conditionTypeParts[] = $db->quote($type);
            }
            if (in_array('folder', $subtypes)) {
                $conditionTypeParts[] = $db->quote('folder');
            }
            $conditionParts[] = '( maintype IN (' . implode(',', $conditionTypeParts) . ') )';
        }

        if ($subtypes[0]) {
            $conditionSubtypeParts = [];
            foreach ($subtypes as $subtype) {
                $conditionSubtypeParts[] = $db->quote($subtype);
            }
            $conditionParts[] = '( `type` IN (' . implode(',', $conditionSubtypeParts) . ') )';
        }

        if ($classnames[0]) {
            if (in_array('folder', $subtypes)) {
                $classnames[] = 'folder';
            }
            $conditionClassnameParts = [];
            foreach ($classnames as $classname) {
                $conditionClassnameParts[] = $db->quote($classname);
            }
            $conditionParts[] = '( subtype IN (' . implode(',', $conditionClassnameParts) . ') )';
        }

        //filtering for tags
        if (!empty($allParams['tagIds'])) {
            $tagIds = $allParams['tagIds'];

            $tagsTypeCondition = '';
            if ($types[0]) {
                $tagsTypeCondition = 'ctype IN (\'' . implode('\',\'', $types) . '\') AND';
            }

            foreach ($tagIds as $tagId) {
                if (($allParams['considerChildTags'] ?? 'false') === 'true') {
                    $tag = Element\Tag::getById((int)$tagId);
                    if ($tag) {
                        $tagPath = $tag->getFullIdPath();
                        $conditionParts[] = 'id IN (SELECT cId FROM tags_assignment INNER JOIN tags ON tags.id = tags_assignment.tagid WHERE '.$tagsTypeCondition.' (id = ' .(int)$tagId. ' OR idPath LIKE ' . $db->quote(Helper::escapeLike($tagPath) . '%') . '))';
                    }
                } else {
                    $conditionParts[] = 'id IN (SELECT cId FROM tags_assignment WHERE '.$tagsTypeCondition.' tagid = ' .(int)$tagId. ')';
                }
            }
        }

        $condition = implode(' AND ', $conditionParts);
        $searcherList->setCondition($condition);
        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        $searcherList->setOrderKey($queryCondition, false);
        $searcherList->setOrder('DESC');

        $sortingSettings = $this->extractSortingSettings($allParams);

        if ($sortingSettings['orderKey'] ?? false) {
            // Order by key column instead of filename
            $orderKeyQuote = true;
            if ($sortingSettings['orderKey'] === 'filename') {
                $sortingSettings['orderKey'] = 'CAST(`key` AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci';
                $orderKeyQuote = false;
            }

            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                'classname' => 'subtype',
            ];

            $sort = $sortingSettings['orderKey'];
            if (array_key_exists($sortingSettings['orderKey'], $sortMapping)) {
                $sort = $sortMapping[$sortingSettings['orderKey']];
            }
            $searcherList->setOrderKey($sort, $orderKeyQuote);
        }

        if ($sortingSettings['order'] ?? false) {
            $searcherList->setOrder($sortingSettings['order']);
        }

        $beforeListLoadEvent = new GenericEvent($this, [
            'list' => $searcherList,
            'context' => $allParams,
        ]);
        $eventDispatcher->dispatch($beforeListLoadEvent, AdminSearchEvents::SEARCH_LIST_BEFORE_LIST_LOAD);
        /** @var Data\Listing $searcherList */
        $searcherList = $beforeListLoadEvent->getArgument('list');

        if (in_array('asset', $types)) {
            // Global asset list event (same than the SEARCH_LIST_BEFORE_LIST_LOAD event, but this last one is global for search, list, tree)
            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $searcherList,
                'context' => $allParams,
            ]);

            if (class_exists(AdminEvents::class)) {
                $eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::ASSET_LIST_BEFORE_LIST_LOAD);
                /** @var Data\Listing $searcherList */
                $searcherList = $beforeListLoadEvent->getArgument('list');
            }
        }

        if (in_array('document', $types) && class_exists(AdminEvents::class)) {
            // Global document list event (same than the SEARCH_LIST_BEFORE_LIST_LOAD event, but this last one is global for search, list, tree)
            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $searcherList,
                'context' => $allParams,
            ]);
            $eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD);
            /** @var Data\Listing $searcherList */
            $searcherList = $beforeListLoadEvent->getArgument('list');
        }

        if (in_array('object', $types)) {
            // Global object list event (same than the SEARCH_LIST_BEFORE_LIST_LOAD event, but this last one is global for search, list, tree)
            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $searcherList,
                'context' => $allParams,
            ]);

            if (class_exists(AdminEvents::class)) {
                $eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD);
                /** @var Data\Listing $searcherList */
                $searcherList = $beforeListLoadEvent->getArgument('list');
            }
        }

        try {
            $hits = $searcherList->load();
        } catch (SyntaxErrorException $syntaxErrorException) {
            throw new InvalidArgumentException('Check your arguments.');
        }

        $elements = [];
        foreach ($hits as $hit) {
            $element = Element\Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element->isAllowed('list')) {

                $data = null;
                if (class_exists(GridData\DataObject::class)) {
                    $data = match (true) {
                        $element instanceof DataObject\AbstractObject => GridData\DataObject::getData($element, $fields),
                        // @phpstan-ignore-next-line checking dataObject once is enough
                        $element instanceof Document => GridData\Document::getData($element),
                        // @phpstan-ignore-next-line otherwise have to do class_exists for each element type
                        $element instanceof Asset => GridData\Asset::getData($element),
                        default => null
                    };
                } else {
                    // TODO: remove in pimcore/pimcore 12.0, kept only to avoid conflicting admin ui classic bundle < 1.5
                    $data = match (true) {
                        $element instanceof DataObject\AbstractObject => DataObject\Service::gridObjectData($element, $fields),
                        default => null
                    };
                }

                if ($data) {
                    $elements[] = $data;
                }
            } else {
                //TODO: any message that view is blocked?
                //$data = Element\Service::gridElementData($element);
            }
        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if ($allParams['limit']) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($elements);
        }

        $result = ['data' => $elements, 'success' => true, 'total' => $totalMatches];

        $afterListLoadEvent = new GenericEvent($this, [
            'list' => $result,
            'context' => $allParams,
        ]);
        $eventDispatcher->dispatch($afterListLoadEvent, AdminSearchEvents::SEARCH_LIST_AFTER_LIST_LOAD);
        $result = $afterListLoadEvent->getArgument('list');

        return $this->jsonResponse($result);
    }

    /**
     * @throws AdminClassicBundleNotFoundException
     */
    protected function extractSortingSettings(array $params): array
    {
        if (!class_exists(QueryParams::class)) {
            throw new AdminClassicBundleNotFoundException('This action requires package "pimcore/admin-ui-classic-bundle" to be installed.');
        }

        return QueryParams::extractSortingSettings($params);
    }

    /**
     *
     *
     * @internal
     */
    protected function getPermittedPaths(array $types = ['asset', 'document', 'object']): string
    {
        $user = $this->getPimcoreUser();
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

        //if allowedTypes is still empty after getting the workspaces, it means that there are no any main permissions set
        // by setting a `false` condition in the query makes sure that nothing would be displayed.
        if (!$allowedTypes) {
            $allowedTypes = ['false'];
        }

        return '('.implode(' OR ', $allowedTypes) .')';
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
     * @Route("/quicksearch", name="pimcore_bundle_search_search_quicksearch", methods={"GET"})
     *
     *
     */
    public function quickSearchAction(Request $request, EventDispatcherInterface $eventDispatcher): JsonResponse
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
        $eventDispatcher->dispatch($beforeListLoadEvent, AdminSearchEvents::QUICKSEARCH_LIST_BEFORE_LIST_LOAD);
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

                if (class_exists(ElementAdminStyleEvent::class)) {
                    $this->addAdminStyle($element, ElementAdminStyleEvent::CONTEXT_SEARCH, $data);
                }

                $elements[] = $data;
            }
        }

        $afterListLoadEvent = new GenericEvent($this, [
            'list' => $elements,
            'context' => $query,
        ]);
        $eventDispatcher->dispatch($afterListLoadEvent, AdminSearchEvents::QUICKSEARCH_LIST_AFTER_LIST_LOAD);
        $elements = $afterListLoadEvent->getArgument('list');

        $result = ['data' => $elements, 'success' => true];

        return $this->jsonResponse($result);
    }

    /**
     * @Route("/quicksearch-get-by-id", name="pimcore_bundle_search_search_quicksearch_by_id", methods={"GET"})
     *
     *
     */
    public function quickSearchByIdAction(Request $request, Config $config): JsonResponse
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
                    '@PimcoreAdmin/searchadmin/search/quicksearch/' . $hit->getId()->getType() . '.html.twig', [
                        'element' => $element,
                        'iconCls' => $data['iconCls'],
                        'config' => $config,
                        'validLanguages' => $validLanguages,
                    ]
                );
            }
        }

        return $this->jsonResponse($data);
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

    /**
     *
     * @throws Exception
     */
    protected function addAdminStyle(ElementInterface $element, int $context = null, array &$data = []): void
    {
        $event = new ElementAdminStyleEvent($element, new AdminStyle($element), $context);
        Pimcore::getEventDispatcher()->dispatch($event, AdminEvents::RESOLVE_ELEMENT_ADMIN_STYLE);
        $adminStyle = $event->getAdminStyle();

        $data['iconCls'] = $adminStyle->getElementIconClass() !== false ? $adminStyle->getElementIconClass() : null;
        if (!$data['iconCls']) {
            $data['icon'] = $adminStyle->getElementIcon() !== false ? $adminStyle->getElementIcon() : null;
        } else {
            $data['icon'] = null;
        }
        if ($adminStyle->getElementCssClass() !== false) {
            if (!isset($data['cls'])) {
                $data['cls'] = '';
            }
            $data['cls'] .= $adminStyle->getElementCssClass() . ' ';
        }
        $data['qtipCfg'] = $adminStyle->getElementQtipConfig();
    }
}
