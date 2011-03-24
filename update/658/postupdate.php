<?php
$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("alter table users add column `active` tinyint(1) unsigned default 1;");

?>

<b>Release Notes (568):</b>
<br/>
- Added active flag to user<br/>
