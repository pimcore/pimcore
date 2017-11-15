<?php

$db = \Pimcore\Db::get();

$db->query("UPDATE users_permission_definitions SET `key`='tags_configuration' WHERE `key`=\"tags_config\"");