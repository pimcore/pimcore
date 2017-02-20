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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
            throw new \Exception("Tag with ID " . $this->getParam("id") . " not found.");
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
            throw new \Exception("Tag with ID " . $this->getParam("id") . " not found.");
        }
    }

    public function treeGetChildrenByIdAction()
    {
        $showSelection = $this->getParam("showSelection") == "true";
        $assginmentCId = intval($this->getParam("assignmentCId"));
        $assginmentCType = strip_tags($this->getParam("assignmentCType"));

        $assignedTagIds = [];
        if ($assginmentCId && $assginmentCType) {
            $assignedTags = Tag::getTagsForElement($assginmentCType, $assginmentCId);

            foreach ($assignedTags as $assignedTag) {
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

    /**
     * @param Tag $tag
     * @param $showSelection
     * @param $assignedTagIds
     * @param bool $loadChildren
     * @return array
     */
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

        if ($showSelection) {
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

    public function loadTagsForElementAction()
    {
        $assginmentCId = intval($this->getParam("assignmentCId"));
        $assginmentCType = strip_tags($this->getParam("assignmentCType"));

        $assignedTagArray = [];
        if ($assginmentCId && $assginmentCType) {
            $assignedTags = Tag::getTagsForElement($assginmentCType, $assginmentCId);

            foreach ($assignedTags as $assignedTag) {
                $assignedTagArray[] = $this->convertTagToArray($assignedTag, false, []);
            }
        }

        $this->_helper->json($assignedTagArray);
    }

    public function addTagToElementAction()
    {
        $assginmentCId = intval($this->getParam("assignmentElementId"));
        $assginmentCType = strip_tags($this->getParam("assignmentElementType"));
        $tagId = intval($this->getParam("tagId"));

        $tag = Tag::getById($tagId);
        if ($tag) {
            Tag::addTagToElement($assginmentCType, $assginmentCId, $tag);
            $this->_helper->json(['success' => true, 'id' => $tag->getId()]);
        } else {
            $this->_helper->json(['success' => false]);
        }
    }

    public function removeTagFromElementAction()
    {
        $assginmentCId = intval($this->getParam("assignmentElementId"));
        $assginmentCType = strip_tags($this->getParam("assignmentElementType"));
        $tagId = intval($this->getParam("tagId"));

        $tag = Tag::getById($tagId);
        if ($tag) {
            Tag::removeTagFromElement($assginmentCType, $assginmentCId, $tag);
            $this->_helper->json(['success' => true, 'id' => $tag->getId()]);
        } else {
            $this->_helper->json(['success' => false]);
        }
    }


    public function getBatchAssignmentJobsAction()
    {
        $elementId = intval($this->getParam("elementId"));
        $elementType = strip_tags($this->getParam("elementType"));


        $idList = [];
        switch ($elementType) {
            case "object":
                $object = \Pimcore\Model\Object\AbstractObject::getById($elementId);
                if ($object) {
                    $idList = $this->getSubObjectIds($object);
                }
                break;
            case "asset":
                $asset = \Pimcore\Model\Asset::getById($elementId);
                if ($asset) {
                    $idList = $this->getSubAssetIds($asset);
                }
                break;
            case "document":
                $document = \Pimcore\Model\Document::getById($elementId);
                if ($document) {
                    $idList = $this->getSubDocumentIds($document);
                }
                break;
        }

        $size = 2;
        $offset = 0;
        $idListParts = [];
        while ($offset < count($idList)) {
            $idListParts[] = array_slice($idList, $offset, $size);
            $offset += $size;
        }

        $this->_helper->json(['success' => true, 'idLists' => $idListParts, 'totalCount' => count($idList)]);
    }

    /**
     * @param \Pimcore\Model\Object\AbstractObject $object
     * @return mixed
     */
    private function getSubObjectIds(\Pimcore\Model\Object\AbstractObject $object)
    {
        $childsList = new Pimcore\Model\Object\Listing();
        $condition = "o_path LIKE ?";
        if (!$this->getUser()->isAdmin()) {
            $userIds = $this->getUser()->getRoles();
            $userIds[] = $this->getUser()->getId();
            $condition .= " AND (
                (SELECT `view` FROM users_workspaces_object WHERE userId IN (" . implode(',', $userIds) . ") and LOCATE(CONCAT(o_path,o_key),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_object WHERE userId IN (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(o_path,o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
             )";
        }

        $childsList->setCondition($condition, $object->getRealFullPath() . '/%');

        return $childsList->loadIdList();
    }

    /**
     * @param \Pimcore\Model\Asset $asset
     * @return mixed
     */
    private function getSubAssetIds(\Pimcore\Model\Asset $asset)
    {
        $childsList = new Pimcore\Model\Asset\Listing();
        $condition = "path LIKE ?";
        if (!$this->getUser()->isAdmin()) {
            $userIds = $this->getUser()->getRoles();
            $userIds[] = $this->getUser()->getId();
            $condition .= " AND (
                (SELECT `view` FROM users_workspaces_asset WHERE userId IN (\" . implode(',', $userIds) . \") and LOCATE(CONCAT(path,filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_asset WHERE userId IN (\" . implode(',', $userIds) . \") and LOCATE(cpath,CONCAT(path,filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )";
        }

        $childsList->setCondition($condition, $asset->getRealFullPath() . '/%');

        return $childsList->loadIdList();
    }

    /**
     * @param \Pimcore\Model\Document $document
     * @return mixed
     */
    private function getSubDocumentIds(\Pimcore\Model\Document $document)
    {
        $childsList = new Pimcore\Model\Document\Listing();
        $condition = "path LIKE ?";
        if (!$this->getUser()->isAdmin()) {
            $userIds = $this->getUser()->getRoles();
            $userIds[] = $this->getUser()->getId();
            $condition .= " AND (
                (SELECT `view` FROM users_workspaces_document WHERE userId IN (\" . implode(',', $userIds) . \") and LOCATE(CONCAT(path,`key`),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_document WHERE userId IN (\" . implode(',', $userIds) . \") and LOCATE(cpath,CONCAT(path,`key`))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )";
        }

        $childsList->setCondition($condition, $document->getRealFullPath() . '/%');

        return $childsList->loadIdList();
    }

    public function doBatchAssignmentAction()
    {
        $cType = strip_tags($this->getParam("elementType"));
        $assignedTags = json_decode($this->getParam("assignedTags"));
        $elementIds = json_decode($this->getParam("childrenIds"));
        $doCleanupTags = $this->getParam("removeAndApply") == "true";

        Tag::batchAssignTagsToElement($cType, $elementIds, $assignedTags, $doCleanupTags);

        $this->_helper->json(['success' => true]);
    }
}
