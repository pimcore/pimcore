<?php

use Pimcore\Model\Tool\TmpStore;

$tmpStoreId = 'pimcore-5.1-build-161-notice';
if (!TmpStore::get($tmpStoreId)) {
    TmpStore::add($tmpStoreId, 'true', null, 86400 * 30);
    echo '<b>You\'re going to install Pimcore 5.1</b><br />';
    echo 'This release includes breaking changes in the targeting engine and for bundles implementing admin features.<br/>';
    echo 'Please see <a href="https://pimcore.com/docs/5.0.x/Development_Documentation/Installation_and_Upgrade/Upgrade_Notes/Within_V5.html#page_Pimcore-5-1" target="_blank">the upgrade notes</a> for details and restart the update process to continue.';
    exit;
}
