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
 * @package    Site
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Site;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();


    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("sites");
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM sites WHERE id = ?", $id);
        if (!$data["id"]) {
            throw new \Exception("there is no site for the requested id");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function getByRootId($id) {
        $data = $this->db->fetchRow("SELECT * FROM sites WHERE rootId = ?", $id);
        if (!$data["id"]) {
            throw new \Exception("there is no site for the requested rootId");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * @param $domain
     * @throws \Exception
     */
    public function getByDomain($domain) {
        $data = $this->db->fetchRow("SELECT * FROM sites WHERE mainDomain = ? OR domains LIKE ?", array($domain, "%\"" . $domain . "\"%"));
        if (!$data["id"]) {

            // check for wildcards
            // @TODO: refactor this to be more clear
            $sitesRaw = $this->db->fetchAll("SELECT id,domains FROM sites");
            $wildcardDomains = [];
            foreach($sitesRaw as $site) {
                if(!empty($site["domains"]) && strpos($site["domains"], "*")) {
                    $siteDomains = unserialize($site["domains"]);
                    if(is_array($siteDomains) && count($siteDomains) > 0) {
                        foreach($siteDomains as $siteDomain) {
                            if(strpos($siteDomain, "*") !==  false) {
                                $wildcardDomains[$siteDomain] = $site["id"];
                            }
                        }
                    }
                }

            }

            foreach($wildcardDomains as $wildcardDomain => $siteId) {
                if(preg_match("#^" . $wildcardDomain . "$#", $domain)) {
                    $data = $this->db->fetchRow("SELECT * FROM sites WHERE id = ?", array($siteId));
                }
            }

            if (!$data["id"]) {
                throw new \Exception("there is no site for the requested domain");
            }
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $ts = time();
        $this->model->setCreationDate($ts);
        $this->model->setModificationDate($ts);
        $this->db->insert("sites", array("rootId" => $this->model->getRootId()));
        $this->model->setId($this->db->lastInsertId());

        $this->save();
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        $ts = time();
        $this->model->setModificationDate($ts);

        $site = get_object_vars($this->model);

        foreach ($site as $key => $value) {
            if (in_array($key, $this->validColumns)) {

                if (is_array($value) || is_object($value)) {
                    $value = \Pimcore\Tool\Serialize::serialize($value);
                }
                if(is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update("sites", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
        
        $this->model->clearDependentCache();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("sites", $this->db->quoteInto("id = ?", $this->model->getId()));
        
        $this->model->clearDependentCache();
    }
}
