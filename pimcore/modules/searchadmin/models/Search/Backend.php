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
 class Search_Backend{

    /**
     * @var string
     */
     private $backendQuery;

     /**
      * @var array
      */
     private $backendQueryParams;

    protected function createBackendSearchQuery($queryStr, $type= null, $subtype = null, $classname = null, $modifiedRange = null, $createdRange = null, $userOwner = null, $userModification = null, $countOnly=false){

            if($countOnly){
                $selectFields = " count(*) as count ";
            } else {
                $selectFields = " * ";
            }

            $this->backendQuery = "SELECT ".$selectFields."
                FROM search_backend_data d
                WHERE (d.data like ? OR properties like ? )";

            $this->backendQueryParams = array("%$queryStr%","%$queryStr%");

            if (!empty($type)) {
                $this->backendQuery.=" AND maintype = ? ";
                $this->backendQueryParams[] = $type;
            }

            if (!empty($subtype)) {
                $this->backendQuery.=" AND type = ? ";
                $this->backendQueryParams[] = $subtype;
            }

            if (!empty($classname)) {
                $this->backendQuery.=" AND subtype = ? ";
                $this->backendQueryParams[] = $classname;;
            }

            if (is_array($modifiedRange)) {
                    if ($modifiedRange[0] != null) {
                        $this->backendQuery .= " AND modificationDate >= ? ";
                        $this->backendQueryParams[] = $modifiedRange[0];
                    }
                    if ($modifiedRange[1] != null) {
                        $this->backendQuery .= " AND modificationDate <= ? ";
                        $this->backendQueryParams[] = $modifiedRange[1];
                    }
            }

            if (is_array($createdRange)) {
                    if ($createdRange[0] != null) {
                        $this->backendQuery .= " AND creationDate >= ? ";
                        $this->backendQueryParams[] = $createdRange[0];
                    }
                    if ($createdRange[1] != null) {
                        $this->backendQuery .= " AND creationDate <= ? ";
                        $this->backendQueryParams[] = $createdRange[1];
                    }
            }

            if (!empty($userOwner)) {
                $this->backendQuery.= " AND userOwner = ? ";
                $this->backendQueryParams[] = $userOwner;
            }

            if (!empty($userModification)) {
                $this->backendQuery.= " AND userModification = ? ";
                $this->backendQueryParams[] = $userModification;
            }

        Logger::debug($this->backendQuery);
        Logger::debug( $this->backendQueryParams);

    }

    /**
     * @param  $queryStr
     * @param  $webResourceType
     * @param  $subtype
     * @param  $modifiedRange
     * @param  $createdRange
     * @param  $userOwner
     * @param  $userModification
     * @param  $classname
     * @return int
     */
    public function getTotalSearchMatches($queryStr, $webResourceType, $type, $subtype, $modifiedRange = null, $createdRange = null, $userOwner = null, $userModification = null, $classname = null){
        $this->createBackendSearchQuery($queryStr, $webResourceType, $type, $subtype, $modifiedRange, $createdRange, $userOwner, $userModification,$classname,true);
        $db = Pimcore_Resource::get();
        $result =  $db->fetchRow($this->backendQuery,$this->backendQueryParams);
        if($result['count']){
            return $result['count'];
        } else return 0;
    }

     /**
     *
     * @param string $queryStr
     * @param string $field
     * @param string $webResourceType
     * @param string $subtype
     * @param integer[] $modifiedRange
     * @param integer[] $createdRange
     * @return array Zend_Search_Lucene_Search_QueryHit
     */
    public function findInDb($queryStr, $type=null, $subtype=null, $classname = null, $modifiedRange = null, $createdRange = null, $userOwner = null, $userModification = null, $offset=0, $limit=25) {

        $this->createBackendSearchQuery($queryStr, $type, $subtype, $classname, $modifiedRange, $createdRange, $userOwner, $userModification,false);
        $db = Pimcore_Resource::get();
        return $db->fetchAll($this->backendQuery,$this->backendQueryParams);
    }

 

 }