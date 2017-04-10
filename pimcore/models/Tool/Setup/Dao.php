<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Setup;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Tool\Setup $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     */
    public function database()
    {
        $mysqlInstallScript = file_get_contents(PIMCORE_PROJECT_ROOT . "/app/Resources/install/install.sql");

        // remove comments in SQL script
        $mysqlInstallScript = preg_replace("/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/", "", $mysqlInstallScript);

        // get every command as single part
        $mysqlInstallScripts = explode(";", $mysqlInstallScript);

        // execute every script with a separate call, otherwise this will end in a PDO_Exception "unbufferd queries, ..." seems to be a PDO bug after some googling
        foreach ($mysqlInstallScripts as $m) {
            $sql = trim($m);
            if (strlen($sql) > 0) {
                $sql .= ";";
                $this->db->query($sql);
            }
        }
    }

    /**
     * @param $file
     * @throws \Exception
     */
    public function insertDump($file)
    {
        $sql = file_get_contents($file);

        //replace document root placeholder with current document root
        $docRoot = str_replace("\\", "/", PIMCORE_PROJECT_ROOT); // Windows fix
        $sql = str_replace("~~DOCUMENTROOT~~", $docRoot, $sql);

        // install is now PDO only, because Mysqli needs a different handling otherwise (doesn't support batch loading with exec())
        $this->db->exec($sql);

        // set the id of the system user to 0
        $this->db->update("users", ["id" => 0], ["name" => "system"]);
    }

    /**
     * @throws \Exception
     */
    public function contents()
    {
        $this->db->insert("assets", [
            "id" => 1,
            "parentId" => 0,
            "type" => "folder",
            "filename" => "",
            "path" => "/",
            "creationDate" => time(),
            "modificationDate" => time(),
            "userOwner" => 1,
            "userModification" => 1
        ]);
        $this->db->insert("documents", [
            "id" => 1,
            "parentId" => 0,
            "type" => "page",
            "key" => "",
            "path" => "/",
            "index" => 999999,
            "published" => 1,
            "creationDate" => time(),
            "modificationDate" => time(),
            "userOwner" => 1,
            "userModification" => 1
        ]);
        $this->db->insert("documents_page", [
            "id" => 1,
            "controller" => "default",
            "action" => "default",
            "template" => "",
            "title" => "",
            "description" => ""
        ]);
        $this->db->insert("objects", [
            "o_id" => 1,
            "o_parentId" => 0,
            "o_type" => "folder",
            "o_key" => "",
            "o_path" => "/",
            "o_index" => 999999,
            "o_published" => 1,
            "o_creationDate" => time(),
            "o_modificationDate" => time(),
            "o_userOwner" => 1,
            "o_userModification" => 1
        ]);


        $this->db->insert("users", [
            "parentId" => 0,
            "name" => "system",
            "admin" => 1,
            "active" => 1
        ]);
        $this->db->update("users", ["id" => 0], ["name" => "system"]);


        $userPermissions = [
            ["key" => "application_logging"],
            ["key" => "assets"],
            ["key" => "classes"],
            ["key" => "clear_cache"],
            ["key" => "clear_temp_files"],
            ["key" => "document_types"],
            ["key" => "documents"],
            ["key" => "objects"],
            ["key" => "plugins"],
            ["key" => "predefined_properties"],
            ["key" => "routes"],
            ["key" => "seemode"],
            ["key" => "system_settings"],
            ["key" => "thumbnails"],
            ["key" => "translations"],
            ["key" => "redirects"],
            ["key" => "glossary" ],
            ["key" => "reports"],
            ["key" => "recyclebin"],
            ["key" => "seo_document_editor"],
            ["key" => "tags_config"],
            ["key" => "tags_assignment"],
            ["key" => "tags_search"],
            ["key" => "robots.txt"],
            ["key" => "http_errors"],
            ["key" => "tag_snippet_management"],
            ["key" => "qr_codes"],
            ["key" => "targeting"],
            ["key" => "notes_events"],
            ["key" => "backup"],
            ["key" => "emails"],
            ["key" => "website_settings"],
            ["key" => "dashboards"],
            ["key" => "users"],
        ];
        foreach ($userPermissions as $up) {
            $this->db->insert("users_permission_definitions", $up);
        }
    }
}
