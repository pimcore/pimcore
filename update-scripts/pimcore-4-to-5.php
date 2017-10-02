<?php

namespace localMigration;

use Pimcore\Db;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * This script contains all update scripts which were introduced during Pimcore 5 development and are needed when migrating
 * from Pimcore 4 to 5. After upgrading to Pimcore 5, run this scripts with.
 *
 * bin/console pimcore:run-script -c <path-to-this-script>
 */
$migrations = [];

// queries as documented in migration guide
$migrations['pre update scripts'] = function () {
    $db = Db::get();
    $schema = $db->getSchemaManager()->createSchema();

    if (!$schema->getTable('documents_page')->hasColumn('legacy')) {
        $db->query('ALTER TABLE `documents_page` ADD COLUMN `legacy` TINYINT(1) NULL AFTER `personas`');
    }

    if (!$schema->getTable('documents_snippet')->hasColumn('legacy')) {
        $db->query('ALTER TABLE `documents_snippet` ADD COLUMN `legacy` TINYINT(1) NULL AFTER `contentMasterDocumentId`');
    }

    if (!$schema->getTable('documents_newsletter')->hasColumn('legacy')) {
        $db->query('ALTER TABLE `documents_newsletter` ADD COLUMN `legacy` TINYINT(1) NULL');
    }

    if (!$schema->getTable('documents_printpage')->hasColumn('legacy')) {
        $db->query('ALTER TABLE `documents_printpage` ADD COLUMN `legacy` TINYINT(1) NULL');
    }

    if (!$schema->getTable('documents_email')->hasColumn('legacy')) {
        $db->query('ALTER TABLE `documents_email` ADD COLUMN `legacy` TINYINT(1) NULL');
    }

    $db->query("ALTER TABLE `translations_website` CHANGE COLUMN `key` `key` VARCHAR(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin'");
    $db->query("ALTER TABLE `translations_admin` CHANGE COLUMN `key` `key` VARCHAR(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin'");
};

// build 48
$migrations[48] = function () {
    $db = Db::get();
    $schema = $db->getSchemaManager()->createSchema();

    if (!$schema->getTable('schedule_tasks')->hasColumn('version')) {
        $db->query('ALTER TABLE `schedule_tasks` ADD INDEX `version` (`version`);');
    }
};

// build 54
$migrations[54] = function () {
    $fs = new Filesystem();

    $configFile = implode(DIRECTORY_SEPARATOR, [
        PIMCORE_APP_ROOT,
        'config',
        'local',
        'update_54_legacy_naming.yml'
    ]);

    $configData = [
        'pimcore' => [
            'documents' => [
                'editables' => [
                    'naming_strategy' => 'legacy'
                ]
            ]
        ]
    ];

    try {
        $yaml = Yaml::dump($configData, 100);
        $yaml = '# created by build 54 - see https://github.com/pimcore/pimcore/issues/1467' . "\n" . $yaml;

        $fs->dumpFile($configFile, $yaml);
    } catch (\Exception $e) {
        echo sprintf(PHP_EOL . '<p><strong style="color: red">ERROR:</strong> Failed to write YML config to <code>%s</code>: %s</p>' . PHP_EOL, $configFile, $e->getMessage());
        echo <<<EOF
<p>Please add the following configuration manually:<br>
<pre>
pimcore:
    documents:
        editables:
            naming_strategy: legacy
</pre>
</p>
EOF;
    }
};

// build 66
$migrations[66] = function () {
    $list = new \Pimcore\Model\DataObject\Objectbrick\Definition\Listing();
    $list = $list->load();

    if (is_array($list)) {
        foreach ($list as $brickDefinition) {
            if ($brickDefinition instanceof \Pimcore\Model\DataObject\Objectbrick\Definition) {
                $classDefinitions = $brickDefinition->getClassDefinitions();

                if (is_array($classDefinitions)) {
                    foreach ($classDefinitions as &$classDefinition) {
                        $definition = \Pimcore\Model\DataObject\ClassDefinition::getById($classDefinition['classname']);

                        $classDefinition['classname'] = $definition->getName();
                    }
                }

                $brickDefinition->setClassDefinitions($classDefinitions);
                $brickDefinition->save();
            }
        }
    }
};

// build 74
$migrations[74] = function () {
    $db = Db::get();
    $db->query("ALTER TABLE `documents_link` CHANGE COLUMN `internalType` `internalType` ENUM('document','asset','object') NULL DEFAULT NULL AFTER `id`;");
};

// build 90
$migrations[90] = function () {
    $db = Db::get();
    $db->query('ALTER TABLE `tags` CHANGE COLUMN `idPath` `idPath` VARCHAR(190) NULL DEFAULT NULL;');
    $db->query('ALTER TABLE `tracking_events` CHANGE COLUMN `category` `category` VARCHAR(190) NULL DEFAULT NULL;');
    $db->query('ALTER TABLE `tracking_events` CHANGE COLUMN `action` `action` VARCHAR(190) NULL DEFAULT NULL;');
    $db->query('ALTER TABLE `tracking_events` CHANGE COLUMN `label` `label` VARCHAR(190) NULL DEFAULT NULL;');
    $db->query('ALTER TABLE `tracking_events` CHANGE COLUMN `data` `data` VARCHAR(190) NULL DEFAULT NULL;');
    $db->query('ALTER TABLE `users` CHANGE COLUMN `password` `password` VARCHAR(190) NULL DEFAULT NULL;');
    $db->query("ALTER TABLE `website_settings` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT '';");
    $db->query('ALTER TABLE `classificationstore_stores` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT NULL;');
    $db->query("ALTER TABLE `classificationstore_groups` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT '';");
    $db->query("ALTER TABLE `classificationstore_keys` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT '';");
    $db->query('ALTER TABLE `classificationstore_keys` CHANGE COLUMN `type` `type` VARCHAR(190) NULL DEFAULT NULL;');
};

// build 97
function build97Check($fieldDefinitions, $needsSave)
{
    /** @var $fieldDefinition */
    foreach ($fieldDefinitions as $fieldDefinition) {
        if ($fieldDefinition instanceof  \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields) {
            $needsSave = build97Check($fieldDefinition->getFieldDefinitions(), $needsSave);
        } elseif ($fieldDefinition instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations) {
            if (method_exists($fieldDefinition, 'getLazyLoading') && $fieldDefinition->getLazyLoading()) {
                $needsSave |= true;
                $fieldDefinition->setLazyLoading(false);
            }
        }
    }

    return $needsSave;
}

$migrations[97] = function () {
    $list = new \Pimcore\Model\DataObject\Fieldcollection\Definition\Listing();
    $list = $list->load();

    /** @var $collectionDef \Pimcore\Model\DataObject\Fieldcollection\Definition */
    foreach ($list as $collectionDef) {
        $needsSave = false;

        $fieldDefinitions = $collectionDef->getFieldDefinitions();
        $needsSave |= build97Check($fieldDefinitions, $needsSave);

        if ($needsSave) {
            $collectionDef->save();
        }
    }
};

// build 100
$migrations[100] = function () {
    $filesystem = new Filesystem();

    if ($filesystem->exists(PIMCORE_CLASS_DIRECTORY . '/Object')) {
        $filesystem->rename(PIMCORE_CLASS_DIRECTORY . '/Object', PIMCORE_CLASS_DIRECTORY . '/__please_delete_Object');

        $classList = new \Pimcore\Model\DataObject\ClassDefinition\Listing();
        $classes = $classList->load();
        foreach ($classes as $class) {
            $class->save();
        }

        $brickList = new \Pimcore\Model\DataObject\Objectbrick\Definition\Listing();
        $brickList = $brickList->load();
        foreach ($brickList as $brickDef) {
            $brickDef->save();
        }

        $fcList = new \Pimcore\Model\DataObject\Fieldcollection\Definition\Listing();
        $fcList = $fcList->load();
        foreach ($fcList as $collectionDef) {
            $collectionDef->save();
        }
    }
};

foreach ($migrations as $type => $migration) {
    echo sprintf(
        'Executing migration for %s...',
        is_numeric($type) ? 'build ' . $type : $type
    );

    $migration();

    echo "OK\n";
}
