<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Event\Model\TagEvent;
use Pimcore\Event\TagEvents;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Element\Tag\Dao getDao()
 */
final class Tag extends Model\AbstractModel
{
    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * @internal
     *
     * @var string
     */
    protected $name;

    /**
     * @internal
     *
     * @var int
     */
    protected int $parentId = 0;

    /**
     * @internal
     *
     * @var string
     */
    protected $idPath;

    /**
     * @internal
     *
     * @var Tag[]
     */
    protected $children;

    /**
     * @internal
     *
     * @var Tag|null
     */
    protected $parent;

    /**
     * @static
     *
     * @param int $id
     *
     * @return Tag|null
     */
    public static function getById($id)
    {
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
     * @param string $cType
     * @param int $cId
     *
     * @return Tag[]
     */
    public static function getTagsForElement($cType, $cId)
    {
        $tag = new Tag();

        return $tag->getDao()->getTagsForElement($cType, $cId);
    }

    /**
     * adds given tag to element
     *
     * @param string $cType
     * @param int $cId
     * @param Tag $tag
     */
    public static function addTagToElement($cType, $cId, Tag $tag)
    {
        $event = new TagEvent($tag, [
            'elementType' => $cType,
            'elementId' => $cId,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, TagEvents::PRE_ADD_TO_ELEMENT);

        $tag->getDao()->addTagToElement($cType, $cId);

        \Pimcore::getEventDispatcher()->dispatch($event, TagEvents::POST_ADD_TO_ELEMENT);
    }

    /**
     * removes given tag from element
     *
     * @param string $cType
     * @param int $cId
     * @param Tag $tag
     */
    public static function removeTagFromElement($cType, $cId, Tag $tag)
    {
        $event = new TagEvent($tag, [
            'elementType' => $cType,
            'elementId' => $cId,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, TagEvents::PRE_REMOVE_FROM_ELEMENT);

        $tag->getDao()->removeTagFromElement($cType, $cId);

        \Pimcore::getEventDispatcher()->dispatch($event, TagEvents::POST_REMOVE_FROM_ELEMENT);
    }

    /**
     * sets given tags to element and removes all other tags
     * to remove all tags from element, provide empty array of tags
     *
     * @param string $cType
     * @param int $cId
     * @param Tag[] $tags
     */
    public static function setTagsForElement($cType, $cId, array $tags)
    {
        $tag = new Tag();
        $tag->getDao()->setTagsForElement($cType, $cId, $tags);
    }

    /**
     * @param string $cType
     * @param array $cIds
     * @param array $tagIds
     * @param bool $replace
     */
    public static function batchAssignTagsToElement($cType, array $cIds, array $tagIds, $replace = false)
    {
        $tag = new Tag();
        $tag->getDao()->batchAssignTagsToElement($cType, $cIds, $tagIds, $replace);
    }

    /**
     * Retrieves all elements that have a specific tag or one of its child tags assigned
     *
     * @param Tag    $tag               The tag to search for
     * @param string $type              The type of elements to search for: 'document', 'asset' or 'object'
     * @param array  $subtypes          Filter by subtypes, eg. page, object, email, folder etc.
     * @param array  $classNames        For objects only: filter by classnames
     * @param bool   $considerChildTags Look for elements having one of $tag's children assigned
     *
     * @return array
     */
    public static function getElementsForTag(
        Tag $tag,
        $type,
        array $subtypes = [],
        $classNames = [],
        $considerChildTags = false
    ) {
        return $tag->getDao()->getElementsForTag($tag, $type, $subtypes, $classNames, $considerChildTags);
    }

    /**
     * @param string $path name path of tags
     *
     * @return Tag|null
     */
    public static function getByPath($path)
    {
        try {
            return (new self)->getDao()->getByPath($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function save()
    {
        $isUpdate = $this->exists();

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new TagEvent($this), TagEvents::PRE_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new TagEvent($this), TagEvents::PRE_ADD);
        }

        $this->correctPath();
        $this->getDao()->save();

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new TagEvent($this), TagEvents::POST_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new TagEvent($this), TagEvents::POST_ADD);
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     *
     * @return Tag
     */
    public function setId(?int $id)
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
     *
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
    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return Tag
     */
    public function setParentId(int $parentId)
    {
        $this->parentId = $parentId;
        $this->parent = null;
        $this->correctPath();

        return $this;
    }

    /**
     * @return Tag|null
     */
    public function getParent(): ?Tag
    {
        if ($this->parent === null && $parentId = $this->getParentId()) {
            $this->parent = self::getById($parentId);
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
    public function getFullIdPath()
    {
        return $this->getIdPath() . $this->getId() . '/';
    }

    /**
     * @param bool $includeOwnName
     *
     * @return string
     */
    public function getNamePath($includeOwnName = true)
    {
        //set id path to correct value
        $parentNames = [];
        if ($includeOwnName) {
            $parentNames[] = $this->getName();
        }
        $parent = $this->getParent();
        while ($parent) {
            $parentNames[] = $parent->getName();
            $parent = $parent->getParent();
        }

        $parentNames = array_reverse($parentNames);

        return '/' . implode('/', $parentNames);
    }

    public function __toString()
    {
        return $this->getNamePath();
    }

    /**
     * @return Tag[]
     */
    public function getChildren()
    {
        if ($this->children == null) {
            $listing = new Tag\Listing();
            $listing->setCondition('parentId = ?', $this->getId());
            $listing->setOrderKey('name');
            $this->children = $listing->load();
        }

        return $this->children;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return count($this->getChildren()) > 0;
    }

    public function correctPath()
    {
        //set id path to correct value
        $parentIds = [];
        $parent = $this->getParent();
        while ($parent) {
            $parentIds[] = $parent->getId();
            $parent = $parent->getParent();
        }

        $parentIds = array_reverse($parentIds);
        if ($parentIds) {
            $this->idPath = '/' . implode('/', $parentIds) . '/';
        } else {
            $this->idPath = '/';
        }
    }

    /**
     * Deletes a tag
     *
     * @throws \Exception
     */
    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(new TagEvent($this), TagEvents::PRE_DELETE);

        $this->getDao()->delete();

        \Pimcore::getEventDispatcher()->dispatch(new TagEvent($this), TagEvents::POST_DELETE);
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->getDao()->exists();
    }
}
