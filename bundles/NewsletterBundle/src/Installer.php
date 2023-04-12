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

namespace Pimcore\Bundle\NewsletterBundle;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Installer extends SettingsStoreAwareInstaller
{
    protected const SETTINGS_STORE_SCOPE = 'pimcore_document_types';

    protected const DOCTYPE = 'newsletter';

    protected const STANDARD_DOCUMENT_ENUM_TYPES = [
        'page',
        'link',
        'snippet',
        'folder',
        'hardlink',
        'email',
    ];

    protected const BUNDLE_EXTRA_DOCUMENT_ENUM_TYPES = [
        'newsletter',
    ];

    protected const USER_PERMISSION_CATEGORY = 'Pimcore Newsletter Bundle';

    protected const USER_PERMISSIONS = [
        'newsletters',
    ];

    public function install(): void
    {
        $this->installDatabaseTable();
        $enums = array_unique(array_merge($this->getCurrentEnumTypes(), self::BUNDLE_EXTRA_DOCUMENT_ENUM_TYPES));
        $this->modifyEnumTypes($enums);
        $this->addUserPermission();
        parent::install();
    }

    public function uninstall(): void
    {
        // Only remove doctypes. Cleanup can be done by dev or command
        $output = new ConsoleOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $output->writeln([
            "\n\n<comment>Uninstalling only removes predefined doctypes. To clean up all documents and dependencies</comment>",
            '<comment>Please run <options=bold>bin/console pimcore:documents:cleanup newsletter</></comment>',
            '<comment>-------------------------------------------------------------------------------------</comment>',
        ]);
        $this->removeUserPermission();
        $this->removeNewsLetterDocTypes();
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            // check if the permission already exists
            $permissionExists = $db->executeStatement('SELECT `key` FROM users_permission_definitions WHERE `key` = :key', ['key' => $permission]);
            if(!$permissionExists) {
                $db->insert('users_permission_definitions', [
                    $db->quoteIdentifier('key') => $permission,
                    $db->quoteIdentifier('category') => self::USER_PERMISSION_CATEGORY,
                ]);
            }
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

    private function getCurrentEnumTypes(): array
    {
        $db = Db::get();

        try {
            $result = $db->executeQuery("SHOW COLUMNS FROM `documents` LIKE 'type'");
            $typeColumn = $result->fetchAllAssociative();

            return explode("','", preg_replace("/(enum)\('(.+?)'\)/", '\\2', $typeColumn[0]['Type']));
        } catch (\Exception $ex) {
            // nothing to do here if it does not work we return the standard types
        }

        return self::STANDARD_DOCUMENT_ENUM_TYPES;
    }

    private function modifyEnumTypes(array $enums): void
    {
        $type = Connection::PARAM_STR_ARRAY;
        if (class_exists('Doctrine\\DBAL\\ArrayParameterType')) {
            $type = ArrayParameterType::STRING;
        }
        $db = Db::get();
        $db->executeQuery('ALTER TABLE documents MODIFY COLUMN `type` ENUM(:enums);', ['enums' => $enums], ['enums' => $type]);
    }

    private function removeNewsLetterDocTypes(): void
    {
        foreach(SettingsStore::getIdsByScope(self::SETTINGS_STORE_SCOPE) as $id) {
            $newsletterDocType = SettingsStore::get($id, self::SETTINGS_STORE_SCOPE);
            if($newsletterDocType) {
                $data = json_decode($newsletterDocType->getData(), true);
                if(!empty($data) && $data['type'] === self::DOCTYPE) {
                    SettingsStore::delete($id, self::SETTINGS_STORE_SCOPE);
                }
            }
        }
    }
}
