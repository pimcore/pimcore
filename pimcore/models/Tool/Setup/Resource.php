<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Setup;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     *
     */
    public function database () {
        
        $mysqlInstallScript = file_get_contents(PIMCORE_PATH . "/modules/install/mysql/install.sql");

        // remove comments in SQL script
        $mysqlInstallScript = preg_replace("/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/","",$mysqlInstallScript);

        // get every command as single part
        $mysqlInstallScripts = explode(";",$mysqlInstallScript);

        // execute every script with a separate call, otherwise this will end in a PDO_Exception "unbufferd queries, ..." seems to be a PDO bug after some googling
        foreach ($mysqlInstallScripts as $m) {
            $sql = trim($m);
            if(strlen($sql) > 0) {
                $sql .= ";";
                $this->db->query($m);
            }
        }

        // reset the database connection
        \Pimcore\Resource::reset();
    }

    /**
     * @param $file
     * @throws \Zend_Db_Adapter_Exception
     */
	public function insertDump($file) {

		$sql = file_get_contents($file);
		
		// we have to use the raw connection here otherwise \Zend_Db uses prepared statements, which causes problems with inserts (: placeholders)
		// and mysqli causes troubles because it doesn't support multiple queries
		if($this->db->getResource() instanceof \Zend_Db_Adapter_Mysqli) {
			$mysqli = $this->db->getConnection();
			$mysqli->multi_query($sql);
			
			// loop through results, because ->multi_query() is asynchronous
			do {
				if($result = $mysqli->store_result()){
					$mysqli->free_result();
				}
			} while($mysqli->next_result());
			
		} else if ($this->db->getResource() instanceof \Zend_Db_Adapter_Pdo_Mysql) {
			$this->db->getConnection()->exec($sql);
		}
				
		\Pimcore\Resource::reset();

        // set the id of the system user to 0
        $this->db->update("users",array("id" => 0), $this->db->quoteInto("name = ?", "system"));
	}

    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    public function contents () {

        $this->db->insert("assets", array(
            "id" => 1,
            "parentId" => 0,
            "type" => "folder",
            "filename" => "",
            "path" => "/",
            "creationDate" => time(),
            "modificationDate" => time(),
            "userOwner" => 1,
            "userModification" => 1
        ));
        $this->db->insert("documents", array(
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
        ));
        $this->db->insert("documents_page", array(
            "id" => 1,
            "controller" => "",
            "action" => "",
            "template" => "",
            "title" => "",
            "description" => "",
            "keywords" => ""
        ));
        $this->db->insert("objects", array(
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
        ));


        $this->db->insert("users", array(
            "parentId" => 0,
            "name" => "system",
            "admin" => 1,
            "active" => 1
        ));
        $this->db->update("users",array("id" => 0), $this->db->quoteInto("name = ?", "system"));


        $userPermissions = array(
            array("key" => "assets"),
            array("key" => "classes"),
            array("key" => "clear_cache"),
            array("key" => "clear_temp_files"),
            array("key" => "document_types"),
            array("key" => "documents"),
            array("key" => "objects"),
            array("key" => "plugins"),
            array("key" => "predefined_properties"),
            array("key" => "routes"),
            array("key" => "seemode"),
            array("key" => "system_settings"),
            array("key" => "thumbnails"),
            array("key" => "translations"),
            array("key" => "redirects"),
            array("key" => "glossary" ),
            array("key" => "reports"),
            array("key" => "document_style_editor"),
            array("key" => "recyclebin"),
            array("key" => "sent_emails"),
            array("key" => "seo_document_editor"),
            array("key" => "robots.txt"),
            array("key" => "http_errors"),
            array("key" => "tag_snippet_management"),
            array("key" => "qr_codes"),
            array("key" => "targeting"),
            array("key" => "notes_events"),
            array("key" => "backup"),
            array("key" => "emails"),
            array("key" => "website_settings"),
            array("key" => "newsletter"),
            array("key" => "dashboards"),
            array("key" => "users"),
        );
        foreach ($userPermissions as $up) {
            $this->db->insert("users_permission_definitions", $up);
        }
    }
}
