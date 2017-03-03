<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Searchadmin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Event\AdminEvents;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Object;
use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/search")
 */
class SearchController extends AdminController
{
    /**
     * @Route("/find")
     * @param Request $request
     * @return JsonResponse
     *
     * @todo: $forbiddenConditions could be undefined
     * @todo: $conditionTypeParts could be undefined
     * @todo: $conditionSubtypeParts could be undefined
     * @todo: $conditionClassnameParts could be undefined
     * @todo: $data could be undefined
     */
    public function findAction(Request $request)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $filterPrepareEvent = new GenericEvent($this, [
            "requestParams" => $allParams
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::SEARCH_LIST_BEFORE_FILTER_PREPARE, $filterPrepareEvent);

        $allParams = $filterPrepareEvent->getArgument("requestParams");
        $user = $this->getUser();

        $query = $allParams["query"];
        if ($query == "*") {
            $query = "";
        }

        $query = str_replace("%", "*", $query);
        $query = preg_replace("@([^ ])\-@", "$1 ", $query);

        $types = explode(",", $allParams["type"]);
        $subtypes = explode(",", $allParams["subtype"]);
        $classnames = explode(",", $allParams["class"]);

        if ($allParams["type"] == "object" && is_array($classnames) && empty($classnames[0])) {
            $subtypes = ["object", "variant", "folder"];
        }

        $offset = intval($allParams["start"]);
        $limit = intval($allParams["limit"]);

        $offset = $offset ? $offset : 0;
        $limit = $limit ? $limit : 50;

        $searcherList = new Data\Listing();
        $conditionParts = [];
        $db = \Pimcore\Db::get();

        //exclude forbidden assets
        if (in_array("asset", $types)) {
            if (!$user->isAllowed("assets")) {
                $forbiddenConditions[] = " `type` != 'asset' ";
            } else {
                $forbiddenAssetPaths = Element\Service::findForbiddenPaths("asset", $user);
                if (count($forbiddenAssetPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenAssetPaths); $i++) {
                        $forbiddenAssetPaths[$i] = " (maintype = 'asset' AND fullpath not like " . $db->quote($forbiddenAssetPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] = implode(" AND ", $forbiddenAssetPaths) ;
                }
            }
        }


        //exclude forbidden documents
        if (in_array("document", $types)) {
            if (!$user->isAllowed("documents")) {
                $forbiddenConditions[] = " `type` != 'document' ";
            } else {
                $forbiddenDocumentPaths = Element\Service::findForbiddenPaths("document", $user);
                if (count($forbiddenDocumentPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenDocumentPaths); $i++) {
                        $forbiddenDocumentPaths[$i] = " (maintype = 'document' AND fullpath not like " . $db->quote($forbiddenDocumentPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] =  implode(" AND ", $forbiddenDocumentPaths) ;
                }
            }
        }

        //exclude forbidden objects
        if (in_array("object", $types)) {
            if (!$user->isAllowed("objects")) {
                $forbiddenConditions[] = " `type` != 'object' ";
            } else {
                $forbiddenObjectPaths = Element\Service::findForbiddenPaths("object", $user);
                if (count($forbiddenObjectPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenObjectPaths); $i++) {
                        $forbiddenObjectPaths[$i] = " (maintype = 'object' AND fullpath not like " . $db->quote($forbiddenObjectPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] = implode(" AND ", $forbiddenObjectPaths);
                }
            }
        }

        if ($forbiddenConditions) {
            $conditionParts[] = "(" . implode(" AND ", $forbiddenConditions) . ")";
        }


        if (!empty($query)) {
            $queryCondition = "( MATCH (`data`,`properties`) AGAINST (" . $db->quote($query) . " IN BOOLEAN MODE) )";

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
        if ($allParams["fields"]) {
            $fields = $allParams["fields"];

            foreach ($fields as $f) {
                $parts = explode("~", $f);
                if (substr($f, 0, 1) == "~") {
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
        if ($allParams["filter"] && $allParams["class"]) {
            $class = Object\ClassDefinition::getByName($allParams["class"]);

            // add Localized Fields filtering
            $params = $this->decodeJson($allParams['filter']);
            $unlocalizedFieldsFilters = [];
            $localizedFieldsFilters = [];

            foreach ($params as $paramConditionObject) {
                //this loop divides filter parameters to localized and unlocalized groups
                $definitionExists = in_array('o_' . $paramConditionObject['property'], Object\Service::getSystemFields())
                    || $class->getFieldDefinition($paramConditionObject['property']);
                if ($definitionExists) { //TODO: for sure, we can add additional condition like getLocalizedFieldDefinition()->getFieldDefiniton(...
                    $unlocalizedFieldsFilters[] = $paramConditionObject;
                } else {
                    $localizedFieldsFilters[] = $paramConditionObject;
                }
            }

            //get filter condition only when filters array is not empty

            //string statements for divided filters
            $conditionFilters = count($unlocalizedFieldsFilters)
                ? Object\Service::getFilterCondition(\Zend_Json::encode($unlocalizedFieldsFilters), $class)
                : null;
            $localizedConditionFilters = count($localizedFieldsFilters)
                ?  Object\Service::getFilterCondition(\Zend_Json::encode($localizedFieldsFilters), $class)
                : null;

            $join = "";
            foreach ($bricks as $ob) {
                $join .= " LEFT JOIN object_brick_query_" . $ob . "_" . $class->getId();

                $join .= " `" . $ob . "`";
                $join .= " ON `" . $ob . "`.o_id = `object_" . $class->getId() . "`.o_id";
            }

            if (null !== $conditionFilters) {
                //add condition query for non localised fields
                $conditionParts[] = "( id IN (SELECT `object_" . $class->getId() . "`.o_id FROM object_" . $class->getId()
                    . $join . " WHERE " . $conditionFilters . ") )";
            }

            if (null !== $localizedConditionFilters) {
                //add condition query for localised fields
                $conditionParts[] = "( id IN (SELECT `object_localized_data_" . $class->getId()
                    . "`.ooo_id FROM object_localized_data_" . $class->getId() . $join . " WHERE "
                    . $localizedConditionFilters . " GROUP BY ooo_id " . ") )";
            }
        }

        if (is_array($types) and !empty($types[0])) {
            foreach ($types as $type) {
                $conditionTypeParts[] = $db->quote($type);
            }
            if (in_array("folder", $subtypes)) {
                $conditionTypeParts[] = $db->quote('folder');
            }
            $conditionParts[] = "( maintype IN (" . implode(",", $conditionTypeParts) . ") )";
        }

        if (is_array($subtypes) and !empty($subtypes[0])) {
            foreach ($subtypes as $subtype) {
                $conditionSubtypeParts[] = $db->quote($subtype);
            }
            $conditionParts[] = "( type IN (" . implode(",", $conditionSubtypeParts) . ") )";
        }

        if (is_array($classnames) and !empty($classnames[0])) {
            if (in_array("folder", $subtypes)) {
                $classnames[]="folder";
            }
            foreach ($classnames as $classname) {
                $conditionClassnameParts[] = $db->quote($classname);
            }
            $conditionParts[] = "( subtype IN (" . implode(",", $conditionClassnameParts) . ") )";
        }


        //filtering for tags
        $tagIds = $allParams["tagIds"];
        if ($tagIds) {
            foreach ($tagIds as $tagId) {
                foreach ($types as $type) {
                    if ($allParams["considerChildTags"] =="true") {
                        $tag = Element\Tag::getById($tagId);
                        if ($tag) {
                            $tagPath = $tag->getFullIdPath();
                            $conditionParts[] = "id IN (SELECT cId FROM tags_assignment INNER JOIN tags ON tags.id = tags_assignment.tagid WHERE ctype = " . $db->quote($type) . " AND (id = " . intval($tagId) . " OR idPath LIKE " . $db->quote($tagPath . "%") . "))";
                        }
                    } else {
                        $conditionParts[] = "id IN (SELECT cId FROM tags_assignment WHERE ctype = " . $db->quote($type) . " AND tagid = " . intval($tagId) . ")";
                    }
                }
            }
        }


        if (count($conditionParts) > 0) {
            $condition = implode(" AND ", $conditionParts);

            //echo $condition; die();
            $searcherList->setCondition($condition);
        }


        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        // do not sort per default, it is VERY SLOW
        //$searcherList->setOrder("desc");
        //$searcherList->setOrderKey("modificationdate");

        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
        if ($sortingSettings['orderKey']) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                "classname" => "subtype"
            ];

            $sort = $sortingSettings['orderKey'];
            if (array_key_exists($sortingSettings['orderKey'], $sortMapping)) {
                $sort = $sortMapping[$sortingSettings['orderKey']];
            }
            $searcherList->setOrderKey($sortingSettings['orderKey']);
        }
        if ($sortingSettings['order']) {
            $searcherList->setOrder($sortingSettings['order']);
        }



        $beforeListLoadEvent = new GenericEvent($this, [
            "list" => $searcherList
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::SEARCH_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
        $searcherList = $beforeListLoadEvent->getArgument("list");

        $hits = $searcherList->load();

        $elements=[];
        foreach ($hits as $hit) {
            $element = Element\Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element->isAllowed("list")) {
                if ($element instanceof Object\AbstractObject) {
                    $data = Object\Service::gridObjectData($element, $fields);
                } elseif ($element instanceof Document) {
                    $data = Document\Service::gridDocumentData($element);
                } elseif ($element instanceof Asset) {
                    $data = Asset\Service::gridAssetData($element);
                }

                $elements[] = $data;
            } else {
                //TODO: any message that view is blocked?
                //$data = Element\Service::gridElementData($element);
            }
        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if ($allParams["limit"]) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($elements);
        }

        $result = ["data" => $elements, "success" => true, "total" => $totalMatches];


        $afterListLoadEvent = new GenericEvent($this, [
            "list" => $result
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::SEARCH_LIST_AFTER_LIST_LOAD, $afterListLoadEvent);
        $result = $afterListLoadEvent->getArgument("list");

        return $this->json($result);
    }
}
