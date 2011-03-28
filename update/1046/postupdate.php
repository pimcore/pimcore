<?php

$sql = '
update properties set name = "navigation_name_legacy" where name = "navigation_name";
update properties set name = "navigation_target_legacy" where name = "navigation_target";
update properties set name = "navigation_accesskey_legacy" where name = "navigation_accesskey_name";
update properties set name = "navigation_parameters_legacy" where name = "navigation_parameters";
update properties set name = "navigation_relation_legacy" where name = "navigation_relation";
update properties set name = "navigation_anchor_legacy" where name = "navigation_anchor";
update properties set name = "navigation_tabindex_legacy" where name = "navigation_tabindex";
update properties set name = "navigation_exclude_legacy" where name = "navigation_exclude";

insert into properties
select p.id as cid,  "document" as ctype, concat(d.path, d.key) as cpath,"navigation_name" as name, "text" as `type`, p.name as  `data`, "0" as inheritable
from  documents_page p
left join documents d on p.id = d.id
where p.name is not null and p.name!="";

insert into properties
select p.id as cid,  "document" as ctype, concat(d.path, d.key) as cpath, "navigation_name" as name, "text" as `type`, p.name as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.name is not null and p.name!="";

insert into properties
select p.id as cid, "document" as ctype, concat(d.path, d.key) as cpath,  "navigation_target" as name, "text" as `type`, p.target as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.target is not null and p.target!="";

insert into properties
select p.id as cid, "document" as ctype, concat(d.path, d.key) as cpath,  "navigation_accesskey" as name, "text" as `type`, p.accesskey as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.accesskey is not null and p.accesskey!="";

insert into properties
select p.id as cid,"document" as ctype, concat(d.path, d.key) as cpath,  "navigation_parameters" as name, "text" as `type`, p.parameters as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.parameters is not null  and p.parameters!="";

insert into properties
select p.id as cid, "document" as ctype,  concat(d.path, d.key) as cpath, "navigation_relation" as name, "text" as `type`, p.rel as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.rel is not null  and p.rel!="";

insert into properties
select p.id as cid,  "document" as ctype, concat(d.path, d.key) as cpath, "navigation_anchor" as name, "text" as `type`, p.anchor as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.anchor is not null  and p.anchor!="";

insert into properties
select p.id as cid, "document" as ctype, concat(d.path, d.key) as cpath, "navigation_title" as name, "text" as `type`, p.title as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.title is not null  and p.title!="";

insert into properties
select p.id as cid, "document" as ctype, concat(d.path, d.key) as cpath, "navigation_tabindex" as name, "text" as `type`, p.tabindex as  `data`, "0" as inheritable
from  documents_link p
left join documents d on p.id = d.id
where p.tabindex is not null  and p.tabindex!="";


alter table documents_page change name DEPRECATED_name varchar(255);
alter table documents_link change name DEPRECATED_name varchar(255);
alter table documents_link change target DEPRECATED_target varchar(255);
alter table documents_link change accesskey DEPRECATED_accesskey varchar(255);
alter table documents_link change parameters DEPRECATED_parameters varchar(255);
alter table documents_link change rel DEPRECATED_rel varchar(255) ;
alter table documents_link change anchor DEPRECATED_anchor varchar(255);
alter table documents_link change title DEPRECATED_title varchar(255);
alter table documents_link change tabindex DEPRECATED_tabindex varchar(255);

';

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->exec($sql);

$cols = $db->fetchAll("describe documents_link");
$failure = true;
if (is_array($cols)) {
    foreach ($cols as $row) {
        if ($row["Field"] == "DEPRECATED_tabindex") {
            $failure = false;
            break;
        }
    }
}

if($failure){
    Logger::log("Database update of build 1046 failed");
} else {
    Logger::log("Database update of build 1046 succeeded");
}

//clear Cache
Pimcore_Model_Cache::clearAll();

if(!$failure){


    $data = $db->fetchAll("select max(id) as id, cid from versions where ctype = 'document' group by cid ");
        foreach($data as $row){
            $version = $row["id"];
            $documentId = $row["cid"];

            if(is_file(PIMCORE_VERSION_DIRECTORY."/document/".$version)){
                $tmpDoc = unserialize(file_get_contents(PIMCORE_VERSION_DIRECTORY."/document/".$version));
                if($tmpDoc instanceof Document_Page){
                    $dbDoc = Document::getById($documentId);
                    $name = $dbDoc->getProperty("navigation_name");
                    if($name){
                        $tmpDoc->setProperty("navigation_name","text",$name,false);
                        $tmpDoc->_fulldump = true;
                        file_put_contents(PIMCORE_VERSION_DIRECTORY."/document/".$version,serialize($tmpDoc));
                    }
                } else if ($tmpDoc instanceof Document_Link){
                    $dbDoc = Document::getById($documentId);
                    $properties["navigation_name"] = $dbDoc->getProperty("navigation_name");
                    $properties["navigation_tabindex"] = $dbDoc->getProperty("navigation_tabindex");
                    $properties["navigation_accesskey"] = $dbDoc->getProperty("navigation_accesskey");
                    $properties["navigation_relation"] = $dbDoc->getProperty("navigation_relation");
                    $properties["navigation_target"] = $dbDoc->getProperty("navigation_target");
                    $properties["navigation_parameters"] = $dbDoc->getProperty("navigation_parameters");
                    $properties["navigation_anchor"] = $dbDoc->getProperty("navigation_anchor");
                    $properties["navigation_title"] = $dbDoc->getProperty("navigation_title");
                    $change = false;
                    foreach($properties as $key => $value){
                        if($value){
                            $tmpDoc->setProperty($key,"text",$value,false);
                        }
                    }
                    if($change){
                        $tmpDoc->_fulldump = true;
                        file_put_contents(PIMCORE_VERSION_DIRECTORY."/document/".$version,serialize($tmpDoc));
                    }

                }

            } else {
                echo "Error: Version" . $version . "was found in database, but does not exist on file system <br/>";
            }

        }
}

//clear Cache
Pimcore_Model_Cache::clearAll();

if($failure){
    echo "UPDATE REQUIRES MANUAL ACTION!<br/> Database update failed. Please check your debug.log and the pimcore upgrade notes and execute the update steps for build 1046 manually.";
} else {
    echo "This update moved some document settings to properties. If there are any existing scheduled tasks for documents in the future, they might lead to data loss if they publish an older version. Please check your scheduled tasks and assign them a new version based on the current data.";
}