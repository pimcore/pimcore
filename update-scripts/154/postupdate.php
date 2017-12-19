<?php

$db = \Pimcore\Db::get();
$db->query('
ALTER TABLE `quantityvalue_units` 
    MODIFY `abbreviation` varchar(20)
;
');
