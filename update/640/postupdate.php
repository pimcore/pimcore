<?php


$db = Pimcore_Resource_Mysql::get();
$db->getConnection()->exec("RENAME TABLE `documents_docTypes` TO `documents_doctypes`;");

?>