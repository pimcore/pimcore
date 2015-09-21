<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("delete from assets_metadata where cid not in(select id from assets)");
