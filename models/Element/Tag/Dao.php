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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Element\Tag;

use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\Element\Tag;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Tag $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM tags WHERE id = ?', [$id]);
        if (!$data) {
            throw new Model\Exception\NotFoundException('Tag item with id ' . $id . ' not found');
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     *
     * @throws Exception
     *
     * @todo: not all save methods return a boolean, why this one?
     */
    public function save(): bool
    {
        if (strlen(trim(strip_tags($this->model->getName()))) < 1) {
            throw new Exception(sprintf('Invalid name for Tag: %s', $this->model->getName()));
        }

        $this->db->beginTransaction();

        try {
            $dataAttributes = $this->model->getObjectVars();

            $originalIdPath = null;
            if ($this->model->getId()) {
                $originalIdPath = $this->db->fetchOne('SELECT idPath FROM tags WHERE id = ?', [$this->model->getId()]);
            }

            $data = [];
            foreach ($dataAttributes as $key => $value) {
                if (in_array($key, $this->getValidTableColumns('tags'))) {
                    $data[$key] = $value;
                }
            }

            Helper::upsert($this->db, 'tags', $data, $this->getPrimaryKey('tags'));

            $lastInsertId = $this->db->lastInsertId();
            if (!$this->model->getId() && $lastInsertId) {
                $this->model->setId((int) $lastInsertId);
            }

            //check for id-path and update it, if path has changed -> update all other tags that have idPath == idPath/id
            if ($originalIdPath && $originalIdPath != $this->model->getIdPath()) {
                $this->db->executeQuery('UPDATE tags SET idPath = REPLACE(idPath, ?, ?)  WHERE idPath LIKE ?;', [$originalIdPath, $this->model->getIdPath(), Helper::escapeLike($originalIdPath) . $this->model->getId() . '/%']);
            }

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * Deletes object from database
     *
     * @throws Exception
     */
    public function delete(): array
    {
        $this->db->beginTransaction();

        try {
            $toRemoveTagIds = $this->db->fetchFirstColumn('SELECT id FROM tags WHERE ' . Helper::quoteInto($this->db, 'idPath LIKE ?', Helper::escapeLike($this->model->getIdPath()) . $this->model->getId() . '/%'));
            $toRemoveTagIds[] = $this->model->getId();
            $implodedTagIds = implode(',', $toRemoveTagIds);

            $this->db->executeStatement('DELETE FROM tags_assignment WHERE tagid IN (' . $implodedTagIds . ')');
            $this->db->executeStatement('DELETE FROM tags WHERE id IN (' . $implodedTagIds . ')');
            $this->db->commit();

            return $toRemoveTagIds;
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }

    }

    /**
     *
     * @return Model\Element\Tag[]
     */
    public function getTagsForElement(string $cType, int $cId): array
    {
        $tags = [];
        $tagIds = $this->db->fetchFirstColumn('SELECT tagid FROM tags_assignment WHERE cid = ? AND ctype = ?', [$cId, $cType]);

        foreach ($tagIds as $tagId) {
            $tags[] = Model\Element\Tag::getById($tagId);
        }

        $tags = array_filter($tags);
        @usort($tags, function ($left, $right) {
            return strcmp($left->getNamePath(), $right->getNamePath());
        });

        return $tags;
    }

    public function addTagToElement(string $cType, int $cId): void
    {
        $this->doAddTagToElement($this->model->getId(), $cType, $cId);
    }

    protected function doAddTagToElement(int $tagId, string $cType, int $cId): void
    {
        $data = [
            'tagid' => $tagId,
            'ctype' => $cType,
            'cid' => $cId,
        ];
        Helper::upsert($this->db, 'tags_assignment', $data, $this->getPrimaryKey('tags_assignment'));
    }

    public function removeTagFromElement(string $cType, int $cId): void
    {
        $this->db->delete('tags_assignment', [
            'tagid' => $this->model->getId(),
            'ctype' => $cType,
            'cid' => $cId,
        ]);
    }

    /**
     *
     * @throws Exception
     */
    public function setTagsForElement(string $cType, int $cId, array $tags): void
    {
        $this->db->beginTransaction();

        try {
            $this->db->delete('tags_assignment', ['ctype' => $cType, 'cid' => $cId]);

            foreach ($tags as $tag) {
                $this->doAddTagToElement($tag->getId(), $cType, $cId);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    public function batchAssignTagsToElement(string $cType, array $cIds, array $tagIds, bool $replace): void
    {
        if ($replace) {
            $quotedCIds = [];
            foreach ($cIds as $cId) {
                $quotedCIds[] = $this->db->quote($cId);
            }
            $this->db->executeStatement('DELETE FROM tags_assignment WHERE ' . 'ctype = ' . $this->db->quote($cType) . ' AND cid IN (' . implode(',', $quotedCIds) . ')');
        }

        foreach ($tagIds as $tagId) {
            foreach ($cIds as $cId) {
                $this->doAddTagToElement($tagId, $cType, $cId);
            }
        }
    }

    /**
     * Retrieves all elements that have a specific tag or one of its child tags assigned
     *
     * @param Tag    $tag               The tag to search for
     * @param string $type              The type of elements to search for: 'document', 'asset' or 'object'
     * @param array  $subtypes          Filter by subtypes, eg. page, object, email, folder etc.
     * @param array  $classNames        For objects only: filter by classnames
     * @param bool $considerChildTags Look for elements having one of $tag's children assigned
     *
     */
    public function getElementsForTag(
        Tag $tag,
        string $type,
        array $subtypes = [],
        array $classNames = [],
        bool $considerChildTags = false
    ): array {
        $elements = [];

        $map = [
            'document' => ['documents', '\Pimcore\Model\Document'],
            'asset' => ['assets', '\Pimcore\Model\Asset'],
            'object' => ['objects', '\Pimcore\Model\DataObject\AbstractObject'],
        ];

        $select = $this->db->createQueryBuilder()->select('*')
                           ->from('tags_assignment')
                           ->andWhere('tags_assignment.ctype = :ctype')->setParameter('ctype', $type);

        if (true === $considerChildTags) {
            $select->innerJoin('tags_assignment', 'tags', 'tags', 'tags.id = tags_assignment.tagid');
            $select->andWhere(
                '(' .
                Helper::quoteInto($this->db, 'tags_assignment.tagid = ?', $tag->getId()) . ' OR ' .
                Helper::quoteInto($this->db, 'tags.idPath LIKE ?', Helper::escapeLike($tag->getFullIdPath()) . '%')
                . ')'
            );
        } else {
            $select->andWhere('tags_assignment.tagid = :tagId')->setParameter('tagId', $tag->getId());
        }

        $select->innerJoin('tags_assignment', $map[$type][0], 'el', 'tags_assignment.cId = el.id');

        if (! empty($subtypes)) {
            foreach ($subtypes as $subType) {
                $quotedSubTypes[] = $this->db->quote($subType);
            }
            $select->andWhere('`type` IN (' . implode(',', $quotedSubTypes) . ')');
        }

        if ('object' === $type && ! empty($classNames)) {
            foreach ($classNames as $cName) {
                $quotedClassNames[] = $this->db->quote($cName);
            }
            $select->andWhere('className IN ( ' .  implode(',', $quotedClassNames) . ' )');
        }

        $res = $this->db->executeQuery((string) $select, $select->getParameters());

        while ($row = $res->fetchAssociative()) {
            $el = $map[$type][1]::getById($row['cid']);
            if ($el) {
                $elements[] = $el;
            }
        }

        return $elements;
    }

    /**
     * @param string $tagPath separated by "/"
     *
     */
    public function getByPath(string $tagPath): ?Tag
    {
        $parentTagId = 0;

        $tag = null;
        $tagPath = ltrim($tagPath, '/');
        foreach (explode('/', $tagPath) as $tagItem) {
            $tags = new Tag\Listing();
            $tags->addConditionParam('name = ?', $tagItem);

            if (empty($parentTagId)) {
                $tags->addConditionParam('parentId = 0 OR parentId IS NULL'); // NULL is allowed by database schema
            } else {
                $tags->addConditionParam('parentId = ?', $parentTagId);
            }

            $tags->setLimit(1);

            $tags = $tags->load();

            if (count($tags) === 0) {
                return null;
            }

            $tag = $tags[0];
            $parentTagId = $tag->getId();
        }

        return $tag;
    }

    public function exists(): bool
    {
        if (is_null($this->model->getId())) {
            return false;
        }

        return (bool) $this->db->fetchOne('SELECT COUNT(*) FROM tags WHERE id = ?', [$this->model->getId()]);
    }
}
