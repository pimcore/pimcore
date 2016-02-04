<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element\Tag;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;

class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT * FROM tags WHERE id = ?", $id);
        if (!$data["id"]) {
            throw new \Exception("Tag item with id " . $id . " not found");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save()
    {
        $this->db->beginTransaction();
        try {
            $dataAttributes = get_object_vars($this->model);

            $originalIdPath = $this->db->fetchOne("SELECT idPath FROM tags WHERE id = ?", $this->model->getId());

            $data = [];
            foreach ($dataAttributes as $key => $value) {
                if (in_array($key, $this->getValidTableColumns("tags"))) {
                    $data[$key] = $value;
                }
            }

            $this->db->insertOrUpdate("tags", $data);

            $lastInsertId = $this->db->lastInsertId();
            if (!$this->model->getId() && $lastInsertId) {
                $this->model->setId($lastInsertId);
            }


            //check for id-path and update it, if path has changed -> update all other tags that have idPath == idPath/id
            if ($originalIdPath && $originalIdPath != $this->model->getIdPath()) {
                $this->db->query("UPDATE tags SET idPath = REPLACE(idPath, ?, ?)  WHERE idPath LIKE ?;", [$originalIdPath, $this->model->getIdPath(), $originalIdPath . $this->model->getId() . "/%"]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->beginTransaction();
        try {
            $this->db->delete("tags_assignment", $this->db->quoteInto("tagid = ?", $this->model->getId()));
            $this->db->delete("tags_assignment", $this->db->quoteInto("tagid IN (SELECT id FROM tags WHERE idPath LIKE ?)", $this->model->getIdPath() . $this->model->getId() . "/%"));

            $this->db->delete("tags", $this->db->quoteInto("id = ?", $this->model->getId()));
            $this->db->delete("tags", $this->db->quoteInto("idPath LIKE ?", $this->model->getIdPath() . $this->model->getId() . "/%"));

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * @param $cType
     * @param $cId
     * @return Model\Element\Tag[]
     */
    public function getTagsForElement($cType, $cId)
    {
        $tags = [];
        $tagIds = $this->db->fetchCol("SELECT tagid FROM tags_assignment WHERE cid = ? AND ctype = ?", [$cId, $cType]);

        foreach ($tagIds as $tagId) {
            $tags[] = Model\Element\Tag::getById($tagId);
        }

        array_filter($tags);
        @usort($tags, function ($left, $right) {
            return strcmp($left->getNamePath(), $right->getNamePath());
        });
        return $tags;
    }

    /**
     * @param $cType
     * @param $cId
     */
    public function addTagToElement($cType, $cId)
    {
        $this->doAddTagToElement($this->model->getId(), $cType, $cId);
    }

    protected function doAddTagToElement($tagId, $cType, $cId)
    {
        $data = [
            "tagid" => $tagId,
            "ctype" => $cType,
            "cid" => $cId
        ];
        $this->db->insertOrUpdate("tags_assignment", $data);
    }

    public function removeTagFromElement($cType, $cId)
    {
        $this->db->delete("tags_assignment",
            "tagid = " . $this->db->quote($this->model->getId()) . " AND ctype = " . $this->db->quote($cType) . " AND cid = " . $this->db->quote($cId));
    }

    public function setTagsForElement($cType, $cId, array $tags)
    {
        $this->db->beginTransaction();
        try {
            $this->db->delete("tags_assignment", "ctype = " . $this->db->quote($cType) . " AND cid = " . $this->db->quote($cId));

            foreach ($tags as $tag) {
                $this->doAddTagToElement($tag->getId(), $cType, $cId);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function batchAssignTagsToElement($cType, array $cIds, array $tagIds, $replace)
    {
        if ($replace) {
            $this->db->delete("tags_assignment", "ctype = " . $this->db->quote($cType) . " AND cid IN (" . implode(",", $cIds) . ")");
        }

        foreach ($tagIds as $tagId) {
            foreach ($cIds as $cId) {
                $this->doAddTagToElement($tagId, $cType, $cId);
            }
        }
    }
}
