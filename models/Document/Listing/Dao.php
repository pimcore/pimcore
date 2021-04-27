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

namespace Pimcore\Model\Document\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * Loads a list of objects (all are an instance of Document) for the given parameters an return them
     *
     * @return array
     */
    public function load()
    {
        $documents = [];
        $select = $this->getQueryBuilder(['id', 'type']);

        $documentsData = $this->db->fetchAll((string) $select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

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
     * @param string|string[]|null $columns
     *
     * @return DoctrineQueryBuilder
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from('documents');

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList()
    {
        $queryBuilder = $this->getQueryBuilder(['id']);
        $documentIds = $this->db->fetchCol((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $documentIds);
    }

    /**
     * @return array
     */
    public function loadIdPathList()
    {
        $queryBuilder = $this->getQueryBuilder(['id', 'CONCAT(path,`key`) as path']);
        $documentIds = $this->db->fetchAll((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

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
        $queryBuilder = $this->getQueryBuilder();
        $this->prepareQueryBuilderForTotalCount($queryBuilder);

        $amount = (int) $this->db->fetchOne((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return $amount;
    }
}
