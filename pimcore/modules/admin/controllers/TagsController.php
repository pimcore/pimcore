<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code. dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

use Pimcore\Model\Element\Tag;

class Admin_TagsController extends \Pimcore\Controller\Action\Admin
{

    public function addAction()
    {

        $tag = new Pimcore\Model\Element\Tag();
        $tag->setName(strip_tags($this->getParam('text')));
        $tag->setParentId(intval($this->getParam('parentId')));
        $tag->save();

        $this->_helper->json(['success' => true, 'id' => $tag->getId()]);
    }

    public function deleteAction()
    {
        $tag = Pimcore\Model\Element\Tag::getById($this->getParam("id"));
        if ($tag) {
            $tag->delete();
            $this->_helper->json(['success' => true]);
        } else {
            throw new Exception("Tag with ID " . $this->getParam("id") . " not found.");
        }
    }

    public function updateAction()
    {

        $tag = Pimcore\Model\Element\Tag::getById($this->getParam("id"));
        if ($tag) {
            $parentId = $this->getParam("parentId");
            if ($parentId || $parentId === "0") {
                $tag->setParentId(intval($parentId));
            }
            if ($this->getParam("text")) {
                $tag->setName(strip_tags($this->getParam("text")));
            }

            $tag->save();

            $this->_helper->json(['success' => true]);
        } else {
            throw new Exception("Tag with ID " . $this->getParam("id") . " not found.");
        }
    }

    public function treeGetChildrenByIdAction()
    {

        $showSelection = $this->getParam("showSelection") == "true";
        $assginmentCId = intval($this->getParam("assignmentCId"));
        $assginmentCType = strip_tags($this->getParam("assignmentCType"));

        $assignedTagIds = [];
        if($assginmentCId && $assginmentCType) {
            $assignedTags = Tag::getTagsForElement($assginmentCType, $assginmentCId);

            foreach($assignedTags as $assignedTag) {
                $assignedTagIds[$assignedTag->getId()] = $assignedTag;
            }
        }

        $tagList = new Pimcore\Model\Element\Tag\Listing();
        if ($this->getParam("node")) {
            $tagList->setCondition("parentId = ?", intval($this->getParam("node")));
        } else {
            $tagList->setCondition("ISNULL(parentId) OR parentId = 0");
        }
        $tagList->setOrderKey("name");

        $tags = [];
        foreach ($tagList->load() as $tag) {
            $tags[] = $this->convertTagToArray($tag, $showSelection, $assignedTagIds, true);
        }

        $this->_helper->json($tags);
    }

    protected function convertTagToArray(Tag $tag, $showSelection, $assignedTagIds, $loadChildren = false)
    {
        $tagArray = [
            "id" => $tag->getId(),
            "text" => $tag->getName(),
            "path" => $tag->getNamePath(),
            "expandable" => $tag->hasChildren(),
            "iconCls" => "pimcore_icon_element_tags", //"/pimcore/static6/img/icon/tag_yellow.png",
            "qtipCfg" => [
                "title" => "ID: " . $tag->getId()
            ]
        ];

        if($showSelection) {
            $tagArray["checked"] = isset($assignedTagIds[$tag->getId()]);
        }

        if ($loadChildren) {
            $children = $tag->getChildren();
            foreach ($children as $child) {
                $tagArray['children'][] = $this->convertTagToArray($child, $showSelection, $assignedTagIds);
            }
        }

        return $tagArray;
    }


    public function loadTagsForElementAction() {
        $assginmentCId = intval($this->getParam("assignmentCId"));
        $assginmentCType = strip_tags($this->getParam("assignmentCType"));

        $assignedTagArray = [];
        if($assginmentCId && $assginmentCType) {
            $assignedTags = Tag::getTagsForElement($assginmentCType, $assginmentCId);

            foreach($assignedTags as $assignedTag) {
                $assignedTagArray[] = $this->convertTagToArray($assignedTag, false, []);
            }
        }

        $this->_helper->json($assignedTagArray);
    }

    public function addTagToElementAction() {
        $assginmentCId = intval($this->getParam("assignmentElementId"));
        $assginmentCType = strip_tags($this->getParam("assignmentElementType"));
        $tagId = intval($this->getParam("tagId"));

        $tag = Tag::getById($tagId);
        if($tag) {
            Tag::addTagToElement($assginmentCType, $assginmentCId, $tag);
            $this->_helper->json(['success' => true, 'id' => $tag->getId()]);
        } else {
            $this->_helper->json(['success' => false]);
        }

    }

    public function removeTagFromElementAction() {
        $assginmentCId = intval($this->getParam("assignmentElementId"));
        $assginmentCType = strip_tags($this->getParam("assignmentElementType"));
        $tagId = intval($this->getParam("tagId"));

        $tag = Tag::getById($tagId);
        if($tag) {
            Tag::removeTagFromElement($assginmentCType, $assginmentCId, $tag);
            $this->_helper->json(['success' => true, 'id' => $tag->getId()]);
        } else {
            $this->_helper->json(['success' => false]);
        }

    }
}