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

class Search_Backend_Tool
{

    /**
     * creates table for backend search, this is a suboptimal approach of assembling data because serialized data is
     * gathered in it's serialized form, but for converting old projects, this is the fastest approach
     * @static
     * @return void
     */
    public static function createSearchDataTable()
    {

        $classesList = new Object_Class_List();
        $classesList->setOrderKey("name");
        $classesList->setOrder("asc");
        $classes = $classesList->load();

        $queries = array();

        $db = Pimcore_Resource::get();
        $fieldcollectionQueries = array();
        $localizedQueries = array();

        //objects
        foreach ($classes as $class) {
            $fieldDefinitions = $class->getFieldDefinitions();
            $names = array();

            //do the "normal" data fields
            foreach ($fieldDefinitions as $fieldDefinition) {
                if (!$fieldDefinition instanceof Object_Class_Data_Relations_Abstract
                    and !$fieldDefinition instanceof Object_Class_Data_Geobounds
                        and !$fieldDefinition instanceof Object_Class_Data_Geopoint
                            and !$fieldDefinition instanceof Object_Class_Data_Geopolygon
                                and (class_exists(Object_Class_Data_Fieldcollections) and !$fieldDefinition instanceof Object_Class_Data_Fieldcollections)
                                    and (class_exists(Object_Class_Data_Localizedfields) and !$fieldDefinition instanceof Object_Class_Data_Localizedfields)
                ) {
                    $names[] = "s." . $fieldDefinition->getName();
                }
            }

            if (count($names) > 0) {
                $queries[] = "SELECT  v.o_id as id, CONCAT(v.o_path,v.o_key) as fullpath, 'object' as maintype, v.o_type as type,  v.o_className as subtype, v.o_published as published, v.o_creationDate as creationDate, v.o_modificationDate as modificationDate, v.o_userOwner as userOwner, v.o_userModification as userModification,
                CONCAT_WS(' ',v.o_key," . implode(",", $names) . ") as data
                FROM object_" . $class->getId() . " v, object_store_" . $class->getId() . " s
                WHERE v.o_id = s.oo_id

                ";
            } else {
                $queries[] = "SELECT  v.o_id as id, CONCAT(v.o_path,v.o_key) as fullpath, 'object' as maintype, v.o_type as `type`,  v.o_className as subtype, v.o_published as published, v.o_creationDate as creationDate, v.o_modificationDate as modificationDate, v.o_userOwner as userOwner, v.o_userModification as userModification,
                v.o_key as data
                FROM object_" . $class->getId() . " v, object_store_" . $class->getId() . " s
                WHERE v.o_id = s.oo_id
                 ";
            }

            //do field collections and localized fields
            foreach ($fieldDefinitions as $fieldDefinition) {
                if (class_exists(Object_Class_Data_Fieldcollections) and  $fieldDefinition instanceof Object_Class_Data_Fieldcollections) {

                    $allowedTypes = $fieldDefinition->getAllowedTypes();
                    if (is_array($allowedTypes)) {
                        foreach ($allowedTypes as $allowedType) {
                            $collectionNames = array();
                            $collection = Object_Fieldcollection_Definition::getByKey($allowedType);
                            $itemDefinitions = $collection->getFieldDefinitions();
                            if (is_array($itemDefinitions)) {
                                foreach ($itemDefinitions as $def) {

                                    if (!$def instanceof Object_Class_Data_Relations_Abstract
                                        and !$def instanceof Object_Class_Data_Geobounds
                                        and !$def instanceof Object_Class_Data_Geopoint
                                        and !$def instanceof Object_Class_Data_Geopolygon) {
                                        $collectionNames[] = "`" . $def->getName() . "`";
                                    }
                                }
                            }
                            if (count($collectionNames) > 0) {
                                $fieldcollectionQueries[] = "SELECT o_id as id, GROUP_CONCAT(CONCAT_WS(' '," . implode(",", $collectionNames) . ")) as fieldcollectiondata FROM object_collection_" . $allowedType . "_" . $class->getId(). " GROUP BY o_id";
                            }
                        }

                    }
                } else if (class_exists(Object_Class_Data_Localizedfields) and  $fieldDefinition instanceof Object_Class_Data_Localizedfields) {

                            $itemDefinitions = $fieldDefinition->getFieldDefinitions();
                            if (is_array($itemDefinitions)) {
                                foreach ($itemDefinitions as $def) {

                                    if (!$def instanceof Object_Class_Data_Relations_Abstract
                                        and !$def instanceof Object_Class_Data_Image
                                        and !$def instanceof Object_Class_Data_Geobounds
                                        and !$def instanceof Object_Class_Data_Geopoint
                                        and !$def instanceof Object_Class_Data_Geopolygon) {
                                        $localizedNames[] = "`" . $def->getName() . "`";
                                    }
                                }
                            }
                            if (count($localizedNames) > 0) {
                                $localizedQueries[] = "SELECT o_id as id, CONCAT_WS(' '," . implode(",", $localizedNames) . ") as localizeddata FROM object_localized_" . $class->getId(). "_default ";
                            }

                }
            }



        }

        $fieldcollectionQuery = null;
        if (count($fieldcollectionQueries) > 0) {
                $fieldcollectionQuery = "CREATE OR REPLACE VIEW search_backend_fieldcollection_view AS " . implode(" UNION ", $fieldcollectionQueries);
                $db->exec($fieldcollectionQuery);
        }

        $localizedQuery = null;
        if (count($localizedQueries) > 0) {
                $localizedQuery = "CREATE OR REPLACE VIEW search_backend_localized_view AS " . implode(" UNION ", $localizedQueries);
                $db->exec($localizedQuery);
        }


        //object folders
        $queries[] = " SELECT o.o_id as id, CONCAT(o.o_path,o.o_key) as fullpath, 'object' as maintype, o.o_type as `type`, o.o_type as subtype, o.o_creationDate as published, o.o_creationDate as creationDate, o.o_modificationDate as modificationDate, o.o_userOwner as userOwner, o.o_userModification as userModification,o.o_key as data  FROM  objects o WHERE o.o_type = 'folder' ";

        //documents
        $queries[] = " SELECT d.id, CONCAT(d.path,d.key) as fullpath, 'document' as maintype,  d.type,  d.type as subtype ,d.published, d.creationDate, d.modificationDate, d.userOwner, d.userModification,CONCAT_WS(' ',d.key,p.name,p.title,p.description,p.keywords,group_concat(e.data)) as data
            FROM documents_elements e, documents d
            LEFT JOIN documents_page p ON (d.id = p.id)
            WHERE e.type in ('textarea','input', 'select','wysiwyg','multiselect','numeric','table')
            AND d.id = e.documentId
            GROUP BY documentId";

        //assets
        $queries[] = " SELECT a.id, CONCAT(a.path,a.filename) as fullpath, 'asset' as maintype, a.type, a.type as subtype, a.creationDate as published, a.creationDate, a.modificationDate, a.userOwner, a.userModification, a.filename as data  FROM  assets a;";

        $query = "CREATE OR REPLACE VIEW search_backend_dataview AS " . implode(" UNION ", $queries);

        //echo $query;


        $db->exec($query);

        $db->exec("DROP TABLE IF EXISTS `search_backend_data`;");

        $db->exec("CREATE TABLE `search_backend_data` (
                       `id` int(11) NOT NULL,
                       `fullpath` VARCHAR(510),
                       `maintype` VARCHAR(8),
                       `type` VARCHAR(20) ,
                       `subtype` VARCHAR(255) ,
                       `published` bigint(20) ,
                       `creationDate` bigint(20) ,
                       `modificationDate` bigint(20) ,
                       `userOwner` int(11) ,
                       `userModification` int(11) ,
                       `data` LONGTEXT ,
                       `fieldcollectiondata` LONGTEXT ,
                       `localizeddata` LONGTEXT ,
                       `properties` TEXT ,
                             PRIMARY KEY  (`id`,`maintype`)
                           ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


        $additionalColumns = "";
        if (count($fieldcollectionQueries) > 0) {
            $additionalColumns.=",fieldcollectiondata";
        }
        if (count($localizedQueries) > 0) {
            $additionalColumns.=",localizeddata";
        }
        $insertQuery = "INSERT INTO search_backend_data (id,fullpath,maintype,type,subtype,published,creationDate,modificationDate,userOwner,userModification,data,properties".$additionalColumns.")
            SELECT id,fullpath,maintype,type,subtype,published,creationDate,modificationDate,userOwner,userModification,data,properties".$additionalColumns."
            FROM search_backend_dataview d
            LEFT JOIN
                (SELECT p.cid, p.ctype, GROUP_CONCAT(p.data) AS properties
                FROM  properties p
                WHERE p.type in ('text','input')
                GROUP BY cid) prop  ON  (prop.cid=d.id AND prop.ctype = d.maintype)

                ";

            if (count($fieldcollectionQueries) > 0) {
                $insertQuery.="
                    LEFT JOIN
                    (SELECT fc.id as fcid, fc.fieldcollectiondata AS  fieldcollectiondata
                    FROM search_backend_fieldcollection_view fc
                    ) fcv ON (fcv.fcid =d.id and d.maintype='object')
                    ";
            }
            if (count($localizedQueries) > 0) {
                $insertQuery.="
                    LEFT JOIN
                    (SELECT l.id as lid, l.localizeddata AS localizeddata
                    FROM search_backend_localized_view l
                    ) lv ON (lv.lid =d.id and d.maintype='object')
                    ";
            }




        $db->exec($insertQuery);


        $db->exec("DROP VIEW IF EXISTS `search_backend_dataview`;");
        $db->exec("DROP VIEW IF EXISTS `search_backend_fieldcollection_view`;");
        $db->exec("DROP VIEW IF EXISTS `search_backend_localized_view`;");
    }

}
