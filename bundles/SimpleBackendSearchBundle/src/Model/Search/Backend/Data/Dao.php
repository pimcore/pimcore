<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;

use Exception;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Dao\AbstractDao;

/**
 * @internal
 *
 * @property Backend\Data $model
 */
class Dao extends AbstractDao
{
    public function getForElement(Model\Element\ElementInterface $element): void
    {
        try {
            if ($element instanceof Model\Document) {
                $maintype = 'document';
            } elseif ($element instanceof Model\Asset) {
                $maintype = 'asset';
            } elseif ($element instanceof Model\DataObject\AbstractObject) {
                $maintype = 'object';
            } else {
                throw new Exception('unknown type of element with id [ '.$element->getId().' ] ');
            }

            $data = $this->db->fetchAssociative('SELECT * FROM search_backend_data WHERE id = ? AND maintype = ? ', [$element->getId(), $maintype]);
            if (is_array($data)) {
                unset($data['id']);
                $data['published'] = (bool) $data['published'];
                $this->assignVariablesToModel($data);
                $this->model->setId(new Backend\Data\Id($element));
            }
        } catch (Exception $e) {
        }
    }

    public function save(): void
    {
        $oldFullPath = $this->db->fetchOne('SELECT fullpath FROM search_backend_data WHERE id = :id and maintype = :type FOR UPDATE', [
            'id' => $this->model->getId()->getId(),
            'type' => $this->model->getId()->getType(),
        ]);

        if ($oldFullPath && $oldFullPath !== $this->model->getFullPath()) {
            $this->db->executeQuery('UPDATE search_backend_data
                SET fullpath = replace(fullpath,' . $this->db->quote($oldFullPath . '/') . ',' . $this->db->quote($this->model->getFullPath() . '/') . ')
                WHERE fullpath LIKE ' . $this->db->quote(Helper::escapeLike($oldFullPath) . '/%') . ' AND maintype = :type',
                [
                    'type' => $this->model->getId()->getType(),
                ]);
        }

        $data = [
            'id' => $this->model->getId()->getId(),
            'key' => $this->model->getKey(),
            'index' => $this->model->getIndex(),
            'fullpath' => $this->model->getFullPath(),
            'maintype' => $this->model->getId()->getType(),
            'type' => $this->model->getType(),
            'subtype' => $this->model->getSubtype(),
            'published' => $this->model->isPublished(),
            'creationDate' => $this->model->getCreationDate(),
            'modificationDate' => $this->model->getmodificationDate(),
            'userOwner' => $this->model->getUserOwner(),
            'userModification' => $this->model->getUserModification(),
            'data' => $this->model->getData(),
            'properties' => $this->model->getProperties(),
        ];

        Helper::upsert($this->db, 'search_backend_data', $data, $this->getPrimaryKey('search_backend_data'));
    }

    /**
     * Deletes from database
     */
    public function delete(): void
    {
        if ($this->model->getId() instanceof Backend\Data\Id) {
            $this->db->delete('search_backend_data', [
                'id' => $this->model->getId()->getId(),
                'maintype' => $this->model->getId()->getType(),
            ]);
        } else {
            Logger::alert('Cannot delete Search\\Backend\\Data, ID is empty');
        }
    }

    public function getMinWordLengthForFulltextIndex(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT @@innodb_ft_min_token_size');
        } catch (Exception $e) {
            return 3;
        }
    }

    public function getMaxWordLengthForFulltextIndex(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT @@innodb_ft_max_token_size');
        } catch (Exception $e) {
            return 84;
        }
    }
}
