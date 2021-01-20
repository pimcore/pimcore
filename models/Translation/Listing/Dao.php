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
 * @package    Translation
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation\Listing;

use Pimcore\Cache;
use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Model;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;

/**
 * @property \Pimcore\Model\Translation\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * @return string
     */
    public function getDatabaseTableName(): string
    {
        return Model\Translation\Dao::TABLE_PREFIX . $this->model->getDomain();
    }

    /**
     * @deprecated
     * @var \Closure
     */
    protected $onCreateQueryCallback;

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $queryBuilder = $this->getQueryBuilderCompatibility([$this->getDatabaseTableName() . '.key']);
        $this->prepareQueryBuilderForTotalCount($queryBuilder);

        $query = sprintf('SELECT COUNT(*) as amount FROM (%s) AS a', (string) $queryBuilder);
        $amount = (int) $this->db->fetchOne($query, $this->model->getConditionVariables());

        return $amount;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if (count($this->model->load()) > 0) {
            return count($this->model->load());
        }

        $queryBuilder = $this->getQueryBuilderCompatibility([$this->getDatabaseTableName() . '.key']);

        $query = sprintf('SELECT COUNT(*) as amount FROM (%s) AS a', (string) $queryBuilder);
        $amount = (int) $this->db->fetchOne($query, $this->model->getConditionVariables());

        return $amount;
    }

    /**
     * @return array
     */
    public function getAllTranslations()
    {
        $cacheKey = $this->getDatabaseTableName().'_data';
        if (!$translations = Cache::load($cacheKey)) {
            $translations = [];

            $queryBuilder = $this->getQueryBuilderCompatibility(['*']);
            $this->prepareQueryBuilderForTotalCount($queryBuilder);
            $translationsData = $this->db->fetchAll((string) $queryBuilder);

            foreach ($translationsData as $t) {
                if (!isset($translations[$t['key']])) {
                    $translations[$t['key']] = new Model\Translation();
                    $translations[$t['key']]->setDomain($this->model->getDomain());
                    $translations[$t['key']]->setKey($t['key']);
                }

                $translations[$t['key']]->addTranslation($t['language'], $t['text']);

                //for legacy support
                if ($translations[$t['key']]->getModificationDate() < $t['creationDate']) {
                    $translations[$t['key']]->setDate($t['creationDate']);
                }

                $translations[$t['key']]->setCreationDate($t['creationDate']);
                $translations[$t['key']]->setModificationDate($t['modificationDate']);
            }

            Cache::save($translations, $cacheKey, ['translator', 'translate'], 999);
        }

        return $translations;
    }

    /**
     * @return array
     */
    public function loadRaw()
    {
        $queryBuilder = $this->getQueryBuilderCompatibility(['*']);
        $translationsData = $this->db->fetchAll((string) $queryBuilder, $this->model->getConditionVariables());

        return $translationsData;
    }

    /**
     * @return array
     */
    public function load()
    {
        $allTranslations = $this->getAllTranslations();
        $translations = [];
        $this->model->setGroupBy($this->getDatabaseTableName() . '.key', false);

        $queryBuilder = $this->getQueryBuilderCompatibility([$this->getDatabaseTableName() . '.key']);
        $translationsData = $this->db->fetchAll((string) $queryBuilder, $this->model->getConditionVariables());

        foreach ($translationsData as $t) {
            $translations[] = $allTranslations[$t['key']];
        }

        $this->model->setTranslations($translations);

        return $translations;
    }

    /**
     * @return bool
     */
    public function isCacheable()
    {
        $count = $this->db->fetchOne('SELECT COUNT(*) FROM ' . $this->getDatabaseTableName());
        $cacheLimit = Model\Translation\Listing::getCacheLimit();
        if ($count > $cacheLimit) {
            return false;
        }

        return true;
    }

    public function cleanup()
    {
        $keysToDelete = $this->db->fetchCol('SELECT `key` FROM ' . $this->getDatabaseTableName() . ' as tbl1 WHERE
               (SELECT count(*) FROM ' . $this->getDatabaseTableName() . " WHERE `key` = tbl1.`key` AND (`text` IS NULL OR `text` = ''))
               = (SELECT count(*) FROM " . $this->getDatabaseTableName() . ' WHERE `key` = tbl1.`key`) GROUP BY `key`;');

        if (is_array($keysToDelete) && !empty($keysToDelete)) {
            $preparedKeys = [];
            foreach ($keysToDelete as $value) {
                $preparedKeys[] = $this->db->quote($value);
            }

            if (!empty($preparedKeys)) {
                $this->db->deleteWhere($this->getDatabaseTableName(), '`key` IN (' . implode(',', $preparedKeys) . ')');
            }
        }
    }

    /**
     * @param string|string[]|null $columns
     *
     * @return DoctrineQueryBuilder
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from($this->getDatabaseTableName());

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * @param array|string|Expression $columns
     *
     * @return \Pimcore\Db\ZendCompatibility\QueryBuilder
     */
    protected function getQuery($columns = '*')
    {
        @trigger_error(sprintf('Using %s is deprecated and will be removed in Pimcore 10, please use getQueryBuilder() instead', __METHOD__), E_USER_DEPRECATED);

        $select = $this->db->select();
        $select->from([ $this->getDatabaseTableName()], $columns);
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
     * @param callable $callback
     */
    public function onCreateQuery(callable $callback)
    {
        @trigger_error(sprintf('Using %s is deprecated and will be removed in Pimcore 10, please use onCreateQueryBuilder() instead', __METHOD__), E_USER_DEPRECATED);
        $this->onCreateQueryCallback = $callback;
    }
}
