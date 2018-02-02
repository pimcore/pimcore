<?php

use Pimcore\Logger;
use Pimcore\Model\Redirect;

$db = \Pimcore\Db::get();

$db->query('
ALTER TABLE `redirects`
    ADD COLUMN `type` VARCHAR(100) DEFAULT NULL AFTER `id`,
    ADD COLUMN `regex` TINYINT(1) DEFAULT NULL AFTER `priority`
;
');

$db->beginTransaction();

try {
    // existing behaviour was always a regex-match, so update existing rows to do so
    $db->query('UPDATE redirects SET regex = 1');

    // update type depending on sourceEntireUrl setting
    $db->executeQuery('UPDATE redirects SET type = ? WHERE sourceEntireUrl = 1', [
        Redirect::TYPE_ENTIRE_URI
    ]);

    $db->executeQuery('UPDATE redirects SET type = ? WHERE sourceEntireUrl <> 1 OR sourceEntireUrl IS NULL', [
        Redirect::TYPE_PATH_QUERY
    ]);

    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();

    Logger::crit($e);
    throw $e;
}

// drop column as it isn't needed anymore
// unfortunately MySQL does not support transactional DDL
// so we need to do this after the transaction succeeded
$db->query('ALTER TABLE redirects DROP COLUMN sourceEntireUrl');
