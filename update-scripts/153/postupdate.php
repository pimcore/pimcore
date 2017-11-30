<?php

//add permission for gdpr_data_extractor
$permission = new \Pimcore\Model\User\Permission\Definition();
$permission->setKey("gdpr_data_extractor");

$res = new \Pimcore\Model\User\Permission\Definition\Dao();
$res->configure(\Pimcore\Db::get());
$res->setModel($permission);
$res->save();
