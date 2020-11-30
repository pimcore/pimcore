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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Event\AdminEvents;
use Pimcore\Model\Element\Tag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tags")
 */
class TagsController extends AdminController
{
    /**
     * @Route("/add", name="pimcore_admin_tags_add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        try {
            $tag = new Tag();
            $tag->setName(strip_tags($request->get('text')));
            $tag->setParentId(intval($request->get('parentId')));
            $tag->save();

            return $this->adminJson(['success' => true, 'id' => $tag->getId()]);
        } catch (\Exception $e) {
            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/delete", name="pimcore_admin_tags_delete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        $tag = Tag::getById($request->get('id'));
        if ($tag) {
            $tag->delete();

            return $this->adminJson(['success' => true]);
        } else {
            throw new \Exception('Tag with ID ' . $request->get('id') . ' not found.');
        }
    }

    /**
     * @Route("/update", name="pimcore_admin_tags_update", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        $tag = Tag::getById($request->get('id'));
        if ($tag) {
            $parentId = $request->get('parentId');
            if ($parentId || $parentId === '0') {
                $tag->setParentId(intval($parentId));
            }
            if ($request->get('text')) {
                $tag->setName(strip_tags($request->get('text')));
            }

            $tag->save();

            return $this->adminJson(['success' => true]);
        } else {
            throw new \Exception('Tag with ID ' . $request->get('id') . ' not found.');
        }
    }

    /**
     * @Route("/tree-get-children-by-id", name="pimcore_admin_tags_treegetchildrenbyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeGetChildrenByIdAction(Request $request)
    {
        $showSelection = $request->get('showSelection') == 'true';
        $assignmentCId = intval($request->get('assignmentCId'));
        $assignmentCType = strip_tags($request->get('assignmentCType'));

        $recursiveChildren = false;
        $assignedTagIds = [];
        if ($assignmentCId && $assignmentCType) {
            $assignedTags = Tag::getTagsForElement($assignmentCType, $assignmentCId);

            foreach ($assignedTags as $assignedTag) {
                $assignedTagIds[$assignedTag->getId()] = $assignedTag;
            }
        }

        $tagList = new Tag\Listing();
        if ($request->get('node')) {
            $tagList->setCondition('parentId = ?', intval($request->get('node')));
        } else {
            $tagList->setCondition('ISNULL(parentId) OR parentId = 0');
        }
        $tagList->setOrderKey('name');

        if (!empty($request->get('filter'))) {
            $filterIds = [0];
            $filterTagList = new Tag\Listing();
            $filterTagList->setCondition('LOWER(`name`) LIKE ?', ['%' . $filterTagList->escapeLike(mb_strtolower($request->get('filter'))) . '%']);
            foreach ($filterTagList->load() as $filterTag) {
                if ($filterTag->getParentId() == 0) {
                    $filterIds[] = $filterTag->getId();
                } else {
                    $ids = explode('/', $filterTag->getIdPath());
                    if (isset($ids[1])) {
                        $filterIds[] = intval($ids[1]);
                    }
                }
            }

            $filterIds = array_unique(array_values($filterIds));
            $tagList->setCondition('id IN('.implode(',', $filterIds).')');
            $recursiveChildren = true;
        }

        $tags = [];
        foreach ($tagList->load() as $tag) {
            $tags[] = $this->convertTagToArray($tag, $showSelection, $assignedTagIds, true, $recursiveChildren);
        }

        return $this->adminJson($tags);
    }

    /**
     * @param Tag $tag
     * @param bool $showSelection
     * @param array $assignedTagIds
     * @param bool $loadChildren
     * @param bool $recursiveChildren
     *
     * @return array
     */
    protected function convertTagToArray(Tag $tag, $showSelection, $assignedTagIds, $loadChildren = false, $recursiveChildren = false)
    {
        $tagArray = [
            'id' => $tag->getId(),
            'text' => $tag->getName(),
            'path' => $tag->getNamePath(),
            'expandable' => $tag->hasChildren(),
            'leaf' => !$tag->hasChildren(),
            'iconCls' => 'pimcore_icon_element_tags',
            'qtipCfg' => [
                'title' => 'ID: ' . $tag->getId(),
            ],
        ];

        if ($showSelection) {
            $tagArray['checked'] = isset($assignedTagIds[$tag->getId()]);
        }

        if ($loadChildren) {
            $children = $tag->getChildren();
            $loadChildren = $recursiveChildren ?? false;
            foreach ($children as $child) {
                $tagArray['children'][] = $this->convertTagToArray($child, $showSelection, $assignedTagIds, $loadChildren, $recursiveChildren);
            }
        }

        return $tagArray;
    }

    /**
     * @Route("/load-tags-for-element", name="pimcore_admin_tags_loadtagsforelement", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function loadTagsForElementAction(Request $request)
    {
        $assginmentCId = intval($request->get('assignmentCId'));
        $assginmentCType = strip_tags($request->get('assignmentCType'));

        $assignedTagArray = [];
        if ($assginmentCId && $assginmentCType) {
            $assignedTags = Tag::getTagsForElement($assginmentCType, $assginmentCId);

            foreach ($assignedTags as $assignedTag) {
                $assignedTagArray[] = $this->convertTagToArray($assignedTag, false, []);
            }
        }

        return $this->adminJson($assignedTagArray);
    }

    /**
     * @Route("/add-tag-to-element", name="pimcore_admin_tags_addtagtoelement", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addTagToElementAction(Request $request)
    {
        $assginmentCId = intval($request->get('assignmentElementId'));
        $assginmentCType = strip_tags($request->get('assignmentElementType'));
        $tagId = intval($request->get('tagId'));

        $tag = Tag::getById($tagId);
        if ($tag) {
            Tag::addTagToElement($assginmentCType, $assginmentCId, $tag);

            return $this->adminJson(['success' => true, 'id' => $tag->getId()]);
        } else {
            return $this->adminJson(['success' => false]);
        }
    }

    /**
     * @Route("/remove-tag-from-element", name="pimcore_admin_tags_removetagfromelement", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeTagFromElementAction(Request $request)
    {
        $assginmentCId = intval($request->get('assignmentElementId'));
        $assginmentCType = strip_tags($request->get('assignmentElementType'));
        $tagId = intval($request->get('tagId'));

        $tag = Tag::getById($tagId);
        if ($tag) {
            Tag::removeTagFromElement($assginmentCType, $assginmentCId, $tag);

            return $this->adminJson(['success' => true, 'id' => $tag->getId()]);
        } else {
            return $this->adminJson(['success' => false]);
        }
    }

    /**
     * @Route("/get-batch-assignment-jobs", name="pimcore_admin_tags_getbatchassignmentjobs", methods={"GET"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function getBatchAssignmentJobsAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $elementId = intval($request->get('elementId'));
        $elementType = strip_tags($request->get('elementType'));

        $idList = [];
        switch ($elementType) {
            case 'object':
                $object = \Pimcore\Model\DataObject\AbstractObject::getById($elementId);
                if ($object) {
                    $idList = $this->getSubObjectIds($object, $eventDispatcher);
                }
                break;
            case 'asset':
                $asset = \Pimcore\Model\Asset::getById($elementId);
                if ($asset) {
                    $idList = $this->getSubAssetIds($asset, $eventDispatcher);
                }
                break;
            case 'document':
                $document = \Pimcore\Model\Document::getById($elementId);
                if ($document) {
                    $idList = $this->getSubDocumentIds($document, $eventDispatcher);
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

        return $this->adminJson(['success' => true, 'idLists' => $idListParts, 'totalCount' => count($idList)]);
    }

    /**
     * @param \Pimcore\Model\DataObject\AbstractObject $object
     *
     * @return int[]
     */
    private function getSubObjectIds(\Pimcore\Model\DataObject\AbstractObject $object, EventDispatcherInterface $eventDispatcher)
    {
        $childsList = new \Pimcore\Model\DataObject\Listing();
        $condition = 'o_path LIKE ?';
        if (!$this->getAdminUser()->isAdmin()) {
            $userIds = $this->getAdminUser()->getRoles();
            $userIds[] = $this->getAdminUser()->getId();
            $condition .= ' AND (
                (SELECT `view` FROM users_workspaces_object WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(CONCAT(o_path,o_key),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_object WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(o_path,o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
             )';
        }

        $childsList->setCondition($condition, $childsList->escapeLike($object->getRealFullPath()) . '/%');

        $beforeListLoadEvent = new GenericEvent($this, [
            'list' => $childsList,
            'context' => [],
        ]);
        $eventDispatcher->dispatch(AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
        /** @var \Pimcore\Model\DataObject\Listing $childsList */
        $childsList = $beforeListLoadEvent->getArgument('list');

        return $childsList->loadIdList();
    }

    /**
     * @param \Pimcore\Model\Asset $asset
     *
     * @return int[]
     */
    private function getSubAssetIds(\Pimcore\Model\Asset $asset, EventDispatcherInterface $eventDispatcher)
    {
        $childsList = new \Pimcore\Model\Asset\Listing();
        $condition = 'path LIKE ?';
        if (!$this->getAdminUser()->isAdmin()) {
            $userIds = $this->getAdminUser()->getRoles();
            $userIds[] = $this->getAdminUser()->getId();
            $condition .= ' AND (
                (SELECT `view` FROM users_workspaces_asset WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path,filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_asset WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path,filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )';
        }

        $childsList->setCondition($condition, $childsList->escapeLike($asset->getRealFullPath()) . '/%');

        $beforeListLoadEvent = new GenericEvent($this, [
            'list' => $childsList,
            'context' => [],
        ]);
        $eventDispatcher->dispatch(AdminEvents::ASSET_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
        /** @var \Pimcore\Model\Asset\Listing $childsList */
        $childsList = $beforeListLoadEvent->getArgument('list');

        return $childsList->loadIdList();
    }

    /**
     * @param \Pimcore\Model\Document $document
     *
     * @return int[]
     */
    private function getSubDocumentIds(\Pimcore\Model\Document $document, EventDispatcherInterface $eventDispatcher)
    {
        $childsList = new \Pimcore\Model\Document\Listing();
        $condition = 'path LIKE ?';
        if (!$this->getAdminUser()->isAdmin()) {
            $userIds = $this->getAdminUser()->getRoles();
            $userIds[] = $this->getAdminUser()->getId();
            $condition .= ' AND (
                (SELECT `view` FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path,`key`),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path,`key`))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )';
        }

        $childsList->setCondition($condition, $childsList->escapeLike($document->getRealFullPath()) . '/%');

        $beforeListLoadEvent = new GenericEvent($this, [
            'list' => $childsList,
            'context' => [],
        ]);
        $eventDispatcher->dispatch(AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
        /** @var \Pimcore\Model\Document\Listing $childsList */
        $childsList = $beforeListLoadEvent->getArgument('list');

        return $childsList->loadIdList();
    }

    /**
     * @Route("/do-batch-assignment", name="pimcore_admin_tags_dobatchassignment", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function doBatchAssignmentAction(Request $request)
    {
        $cType = strip_tags($request->get('elementType'));
        $assignedTags = json_decode($request->get('assignedTags'));
        $elementIds = json_decode($request->get('childrenIds'));
        $doCleanupTags = $request->get('removeAndApply') == 'true';

        Tag::batchAssignTagsToElement($cType, $elementIds, $assignedTags, $doCleanupTags);

        return $this->adminJson(['success' => true]);
    }
}
