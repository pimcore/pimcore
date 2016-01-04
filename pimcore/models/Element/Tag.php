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
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

class Tag extends Model\AbstractModel {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $parentId;

    /**
     * @var string
     */
    public $idPath;

    /**
     * @var Tag[]
     */
    public $children;

    /**
     * @var Tag
     */
    public $parent;


    /**
     * @static
     * @param $id
     * @return Pimcore\Model\Element\Tag
     */
    public static function getById ($id) {
        try {
            $tag = new self();
            $tag->getDao()->getById($id);

            return $tag;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * returns all assigned tags for element
     *
     * @param $cType
     * @param $cId
     * @return Tag[]
     */
    public static function getTagsForElement($cType, $cId) {
        $tag = new Tag();
        return $tag->getDao()->getTagsForElement($cType, $cId);
    }

    /**
     * adds given tag to element
     *
     * @param $cType
     * @param $cId
     * @param Tag $tag
     */
    public static function addTagToElement($cType, $cId, Tag $tag) {
        $tag->getDao()->addTagToElement($cType, $cId);
    }

    /**
     * removes given tag from element
     *
     * @param $cType
     * @param $cId
     * @param Tag $tag
     */
    public static function removeTagFromElement($cType, $cId, Tag $tag) {
        $tag->getDao()->removeTagFromElement($cType, $cId);
    }

    /**
     * sets given tags to element and removes all other tags
     * to remove all tags from element, provide empty array of tags
     *
     * @param $cType
     * @param $cId
     * @param Tag[] $tag
     */
    public static function setTagsForElement($cType, $cId, array $tags) {
        $tag = new Tag();
        $tag->getDao()->setTagsForElement($cType, $cId, $tags);
    }


    public function save() {
        $this->correctPath();
        $this->getDao()->save();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Tag
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     * @return Tag
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
        $this->parent = null;
        $this->correctPath();
        return $this;
    }

    /**
     * @return Tag
     */
    public function getParent() {
        if($this->parent == null) {
            $this->parent = Tag::getById($this->getParentId());
        }
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getIdPath()
    {
        return $this->idPath;
    }

    /**
     * @return string
     */
    public function getFullIdPath() {
        return $this->getIdPath() . $this->getId() . "/";
    }

    public function getNamePath($includeOwnName = true) {
        //set id path to correct value
        $parentNames = [];
        if($includeOwnName) {
            $parentNames[] = $this->getName();
        }
        $parent = $this->getParent();
        while($parent) {
            $parentNames[] = $parent->getName();
            $parent = $parent->getParent();
        }

        $parentNames = array_reverse($parentNames);
        return "/" . implode("/", $parentNames) . "/";
    }

    /**
     * @return Tag[]
     */
    public function getChildren() {
        if($this->children == null) {
            $listing = new Tag\Listing();
            $listing->setCondition("parentId = ?", $this->getId());
            $listing->setOrderKey("name");
            $this->children = $listing->load();
        }
        return $this->children;
    }

    public function hasChildren() {
        return count($this->getChildren()) > 0;
    }


    public function correctPath() {
        //set id path to correct value
        $parentIds = [];
        $parent = $this->getParent();
        while($parent) {
            $parentIds[] = $parent->getId();
            $parent = $parent->getParent();
        }

        $parentIds = array_reverse($parentIds);
        if($parentIds) {
            $this->idPath = "/" . implode("/", $parentIds) . "/";
        } else {
            $this->idPath = "/";
        }
    }

}