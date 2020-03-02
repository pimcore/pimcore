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
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Listing;

use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder;
use Pimcore\Model;
use Pimcore\Model\Document;

/**
 * @property \Pimcore\Model\Document\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * @var \Closure
     */
    protected $onCreateQueryCallback;

    /**
     * Loads a list of objects (all are an instance of Document) for the given parameters an return them
     *
     * @return array
     */
    public function load()
    {
        $documents = [];
        $select = $this->getQuery(['id', 'type']);

        $documentsData = $this->db->fetchAll($select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($documentsData as $documentData) {
            if ($documentData['type']) {
                if ($doc = Document::getById($documentData['id'])) {
                    $documents[] = $doc;
                }
            }
        }

        $this->model->setDocuments($documents);

        return $documents;
    }

    /**
     * @param array|string|Expression $columns
     *
     * @return \Pimcore\Db\ZendCompatibility\QueryBuilder
     */
    public function getQuery($columns = '*')
    {
        $select = $this->db->select();
        $select->from([ 'documents' ], $columns);
        $this->addConditions($select);
        $this->addOrder($select);
        $this->addLimit($select);
        $this->addGroupBy($select);

        if ($this->onCreateQueryCallback) {
            $closure = $this->onCreateQueryCallback;
            $closure($select);
        }

        return $select;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList()
    {
        $select = $this->getQuery(['id']);
        $documentIds = $this->db->fetchCol($select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $documentIds);
    }

    /**
     * @return array
     */
    public function loadIdPathList()
    {
        $select = $this->getQuery(['id', 'CONCAT(path,`key`) as path']);
        $documentIds = $this->db->fetchAll($select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return $documentIds;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getDocuments());
        } else {
            $idList = $this->loadIdList();

            return count($idList);
        }
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $select = $this->getQuery([new Expression('COUNT(*)')]);
        $select->reset(QueryBuilder::LIMIT_COUNT);
        $select->reset(QueryBuilder::LIMIT_OFFSET);
        $select->reset(QueryBuilder::ORDER);

        $amount = (int) $this->db->fetchOne($select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return $amount;
    }

    /**
     * @param callable $callback
     */
    public function onCreateQuery(callable $callback)
    {
        $this->onCreateQueryCallback = $callback;
    }
}
