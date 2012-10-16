<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Searchadmin_SearchController extends Pimcore_Controller_Action_Admin {


    /**
     * @return void
     */
    public function findAction() {

        $user = $this->getUser();

        $query = $this->getParam("query");
        if ($query == "*") {
            $query = "";
        }

        $query = str_replace("%", "*", $query);

        $types = explode(",", $this->getParam("type"));
        $subtypes = explode(",", $this->getParam("subtype"));
        $classnames = explode(",", $this->getParam("class"));

        $offset = intval($this->getParam("start"));
        $limit = intval($this->getParam("limit"));

        $offset = $offset ? $offset : 0;
        $limit = $limit ? $limit : 50;

        $searcherList = new Search_Backend_Data_List();
        $conditionParts = array();
        $db = Pimcore_Resource::get();

        //exclude forbidden assets
        if(in_array("asset", $types)) {
            if (!$user->isAllowed("assets")) {
                $forbiddenConditions[] = " `type` != 'asset' ";
            } else {
                $forbiddenAssetPaths = Element_Service::findForbiddenPaths("asset", $user);
                if (count($forbiddenAssetPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenAssetPaths); $i++) {
                        $forbiddenAssetPaths[$i] = " (maintype = 'asset' AND fullpath not like " . $db->quote($forbiddenAssetPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] = implode(" AND ", $forbiddenAssetPaths) ;
                }
            }
        }


        //exclude forbidden documents
        if(in_array("document", $types)) {
            if (!$user->isAllowed("documents")) {
                $forbiddenConditions[] = " `type` != 'document' ";
            } else {
                $forbiddenDocumentPaths = Element_Service::findForbiddenPaths("document", $user);
                if (count($forbiddenDocumentPaths) > 0) {
                    for ($i = 0; $i < count($forbiddenDocumentPaths); $i++) {
                        $forbiddenDocumentPaths[$i] = " (maintype = 'document' AND fullpath not like " . $db->quote($forbiddenDocumentPaths[$i] . "%") . ")";
                    }
                    $forbiddenConditions[] =  implode(" AND ", $forbiddenDocumentPaths) ;
                }
            }
        }

        //exclude forbidden objects
        if(in_array("object", $types)) {
            if (!$user->isAllowed("objects")) {
                $forbiddenConditions[] = " `type` != 'object' ";
            } else {
                $forbiddenObjectPaths = Element_Service::findForbiddenPaths("object", $user);
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
        $fields = array();
        $bricks = array();
        if($this->getParam("fields")) {
            $fields = $this->getParam("fields");

            foreach($fields as $f) {
                $parts = explode("~", $f);
                if(count($parts) > 1) {
                    $bricks[$parts[0]] = $parts[0];
                }
            }
        }        

        // filtering for objects
        if ($this->getParam("filter")) {
            $class = Object_Class::getByName($this->getParam("class"));
            $conditionFilters = Object_Service::getFilterCondition($this->getParam("filter"), $class);
            $join = "";
            foreach($bricks as $ob) {
                $join .= " LEFT JOIN object_brick_query_" . $ob . "_" . $class->getId();

                $join .= " `" . $ob . "`";
                $join .= " ON `" . $ob . "`.o_id = `object_" . $class->getId() . "`.o_id";
            }

            $conditionParts[] = "( id IN (SELECT `object_" . $class->getId() . "`.o_id FROM object_" . $class->getId() . $join . " " . $conditionFilters . ") )";
        }

        if (is_array($types) and !empty($types[0])) {
            foreach ($types as $type) {
                $conditionTypeParts[] = $db->quote($type);
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
            if(in_array("folder",$subtypes)){
                $classnames[]="folder";    
            }
            foreach ($classnames as $classname) {
                $conditionClassnameParts[] = $db->quote($classname);
            }
            $conditionParts[] = "( subtype IN (" . implode(",", $conditionClassnameParts) . ") )";
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

        if ($this->getParam("sort")) {
            $searcherList->setOrderKey($this->getParam("sort"));
        }
        if ($this->getParam("dir")) {
            $searcherList->setOrder($this->getParam("dir"));
        }



        $hits = $searcherList->load();

        $elements=array();
        foreach ($hits as $hit) {

            $element = Element_Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element->isAllowed("list")) {
                if ($element instanceof Object_Abstract) {
                    $data = Object_Service::gridObjectData($element, $fields);
                } else if ($element instanceof Document) {
                    $data = Document_Service::gridDocumentData($element);
                } else if ($element instanceof Asset) {
                    $data = Asset_Service::gridAssetData($element);
                }

                $elements[] = $data;
            } else {
                //TODO: any message that view is blocked?
                //$data = Element_Service::gridElementData($element);
            }

        }


        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if($this->getParam("limit")) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($elements);
        }

        $this->_helper->json(array("data" => $elements, "success" => true, "total" => $totalMatches));

        $this->removeViewRenderer();


    }


}
