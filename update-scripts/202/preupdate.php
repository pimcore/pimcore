<?php

use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\InstallVersion;

// mark ecommerce install migration as migrated if framework is currently installed
if (PimcoreEcommerceFrameworkBundle::isInstalled()) {
    $db = \Pimcore\Db::get();

    // create migration table if not exists
    $factory = \Pimcore::getContainer()->get('Pimcore\Migrations\Configuration\ConfigurationFactory');
    $bundle = \Pimcore::getKernel()->getBundle('PimcoreEcommerceFrameworkBundle');
    $config = $factory->getForBundle($bundle, $db);
    $config->createMigrationTable();

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
