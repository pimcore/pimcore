<?php

$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE search_backend_data /*!50600 ENGINE=InnoDB */;");
