<?php


$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("alter table recyclebin add column deletedby varchar(50);");

?>

<b>Release Notes (562):</b>
<br/>
- Added deleted by info to recycle bin entry<br/>