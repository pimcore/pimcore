<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\InstallVersion;

// mark ecommerce install migration as migrated if framework is currently installed
if (PimcoreEcommerceFrameworkBundle::isInstalled()) {
    $db = \Pimcore\Db::get();

    $sql = <<<'SQL'
INSERT IGNORE INTO
    pimcore_migrations (migration_set, version, migrated_at)
VALUES
    (:migration_set, :version, NOW())
SQL;

    $db->executeQuery($sql, [
        'migration_set' => 'PimcoreEcommerceFrameworkBundle',
        'version'       => InstallVersion::INSTALL_VERSION,
    ]);
}
