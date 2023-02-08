<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\WebToPrintBundle;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{

    protected const USER_PERMISSION_CATEGORY = 'Pimcore Web2Print Bundle';
    protected const USER_PERMISSIONS = [
        'web2print_settings',
    ];

    protected const STANDARD_DOCUMENT_ENUM_TYPES = [
        'page',
        'link',
        'snippet',
        'folder',
        'hardlink',
        'email',
        'newsletter'
    ];

    protected const BUNDLE_EXTRA_DOCUMENT_ENUM_TYPES = [
        'printpage',
        'printcontainer'
    ];

    public function install(): void
    {
        $this->installDatabaseTable();
        $enums = array_merge($this->getCurrentEnumTypes(), self::BUNDLE_EXTRA_DOCUMENT_ENUM_TYPES);
        $this->modifyEnumTypes($enums);
        $this->addUserPermission();
        parent::install();
    }

    public function uninstall(): void
    {
        $this->removeUserPermission();
        $enums = array_diff($this->getCurrentEnumTypes(), self::BUNDLE_EXTRA_DOCUMENT_ENUM_TYPES);
        $this->modifyEnumTypes($enums);
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USER_PERMISSION_CATEGORY,
            ]);
        }
    }

    private function removeUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }

    private function installDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/install/';
        $sqlFileNames = ['install.sql'];
        $db = \Pimcore\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }

    private function getCurrentEnumTypes() {
        $db = Db::get();
        try {
            $result = $db->executeQuery("SHOW COLUMNS FROM `documents` LIKE 'type'");
            $typeColumn = $result->fetchAllAssociative();
            $enumOptions = explode("','",preg_replace("/(enum)\('(.+?)'\)/","\\2", $typeColumn[0]['Type']));
            if(!empty($enumOptions)) {
                return $enumOptions;
            }
        } catch (\Exception $ex) {
            // nothing to do here if it does not work we return the standard types
        }
        return self::STANDARD_DOCUMENT_ENUM_TYPES;
    }

    private function modifyEnumTypes(array $enums) {
        $db = Db::get();
        $db->executeQuery('ALTER TABLE documents MODIFY COLUMN `type` ENUM(:enums);', ['enums' => $enums], ['enums' => ArrayParameterType::STRING]);
    }
}
