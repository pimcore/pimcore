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
 * @package    Dependency
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Dependency;

use Pimcore\Model;
use Pimcore\Model\Element;
use Pimcore\Logger;

/**
 * @property \Pimcore\Model\Dependency $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * Loads the relations for the given sourceId and type
     *
     * @param integer $id
     * @param string $type
     * @return void
     */
    public function getBySourceId($id = null, $type = null)
    {
        if ($id && $type) {
            $this->model->setSourceId($id);
            $this->model->setSourceType($id);
        }

        // requires
        $data = $this->db->fetchAll("SELECT * FROM dependencies WHERE sourceid = ? AND sourcetype = ?", [$this->model->getSourceId(), $this->model->getSourceType()]);

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $this->model->addRequirement($d["targetid"], $d["targettype"]);
            }
        }

        // required by
        $data = [];
        $data = $this->db->fetchAll("SELECT * FROM dependencies WHERE targetid = ? AND targettype = ?", [$this->model->getSourceId(), $this->model->getSourceType()]);

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $this->model->requiredBy[] = [
                    "id" => $d["sourceid"],
                    "type" => $d["sourcetype"]
                ];
            }
        }
    }

    /**
     * Clear all relations in the database
     * @param Element\ElementInterface $element
     */
    public function cleanAllForElement($element)
    {
        try {
            $id = $element->getId();
            $type = Element\Service::getElementType($element);

            //schedule for sanity check
            $data = $this->db->fetchAll("SELECT * FROM dependencies WHERE targetid = ? AND targettype = ?", [$id, $type]);
            if (is_array($data)) {
                foreach ($data as $row) {
                    $sanityCheck = new Element\Sanitycheck();
                    $sanityCheck->setId($row['sourceid']);
                    $sanityCheck->setType($row['sourcetype']);
                    $sanityCheck->save();
                }
            }

            $this->db->delete("dependencies", $this->db->quoteInto("sourceid = ?", $id) . " AND " . $this->db->quoteInto("sourcetype = ?", $type));
            $this->db->delete("dependencies", $this->db->quoteInto("targetid = ?", $id) . " AND " . $this->db->quoteInto("targettype = ?", $type));
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }


    /**
     * Clear all relations in the database for current source id
     *
     * @return void
     */
    public function clear()
    {
        try {
            $this->db->delete("dependencies", $this->db->quoteInto("sourceid = ?", $this->model->getSourceId()) . " AND " . $this->db->quoteInto("sourcetype = ?", $this->model->getSourceType()));
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * Save to database
     *
     * @return void
     */
    public function save()
    {
        foreach ($this->model->getRequires() as $r) {
            if ($r["id"] && $r["type"]) {
                $this->db->insert("dependencies", [
                    "sourceid" => $this->model->getSourceId(),
                    "sourcetype" => $this->model->getSourceType(),
                    "targetid" => $r["id"],
                    "targettype" => $r["type"]
                ]);
            }
        }
    }
}
