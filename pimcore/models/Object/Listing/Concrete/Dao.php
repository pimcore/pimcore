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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Listing\Concrete;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;

/**
 * @property \Pimcore\Model\Object\Listing\Concrete $model
 */
class Dao extends Model\Object\Listing\Dao
{

    /**
     * @var bool
     */
    protected $firstException = true;

    /**
     * @var string
     */
    private $tableName = null;

    /**
     * @var int
     */
    protected $totalCount = 0;

    /** @var  Callback function */
    protected $onCreateQueryCallback;


    /**
     * get select query
     * @param bool|false $forceNew
     *
     * @return \Zend_Db_Select
     * @throws \Exception
     */
    public function getQuery($forceNew = false)
    {

        // init
        $select = $this->db->select();

        // create base
        $field = $this->getTableName() . ".o_id";
        $select->from(
            [ $this->getTableName() ], [
                new \Zend_Db_Expr(sprintf('%s as o_id', $this->getSelectPart($field, $field))), 'o_type'
            ]
        );

        // add joins
        $this->addJoins($select);

        // add condition
        $this->addConditions($select);

        // group by
        $this->addGroupBy($select);

        // order
        $this->addOrder($select);

        // limit
        $this->addLimit($select);

        if ($this->onCreateQueryCallback) {
            $closure = $this->onCreateQueryCallback;
            $closure($select);
        }

        return $select;
    }

    /**
     * @return array
     * @throws
     */
    public function loadIdList()
    {
        try {
            return parent::loadIdList();
        } catch (\Exception $e) {
            return $this->exceptionHandler($e);
        }
    }

    /**
     * @param $e
     * @return array
     * @throws
     * @throws \Exception
     */
    protected function exceptionHandler($e)
    {

        // create view if it doesn't exist already // HACK
        $pdoMySQL = preg_match("/Base table or view not found/", $e->getMessage());
        $Mysqli = preg_match("/Table (.*) doesn't exist/", $e->getMessage());

        if (($Mysqli || $pdoMySQL) && $this->firstException) {
            $this->firstException = false;

            $localizedFields = new Object\Localizedfield();
            $localizedFields->setClass(Object\ClassDefinition::getById($this->model->getClassId()));
            $localizedFields->createUpdateTable();

            return $this->loadIdList();
        }

        throw $e;
    }

    /**
     * @return string
     * @throws \Exception
     * @throws \Zend_Exception
     */
    public function getTableName()
    {
        if (empty($this->tableName)) {

            // default
            $this->tableName = "object_" . $this->model->getClassId();

            if (!$this->model->getIgnoreLocalizedFields()) {
                $language = null;
                // check for a localized field and if they should be used for this list
                if (property_exists("\\Pimcore\\Model\\Object\\" . ucfirst($this->model->getClassName()), "localizedfields")) {
                    if ($this->model->getLocale()) {
                        if (Tool::isValidLanguage((string) $this->model->getLocale())) {
                            $language = (string) $this->model->getLocale();
                        }
                    }

                    if (!$language && \Zend_Registry::isRegistered("Zend_Locale")) {
                        $locale = \Zend_Registry::get("Zend_Locale");
                        if (Tool::isValidLanguage((string) $locale)) {
                            $language = (string) $locale;
                        }
                    }

                    if (!$language) {
                        $language = Tool::getDefaultLanguage();
                    }

                    if (!$language) {
                        throw new \Exception("No valid language/locale set. Use \$list->setLocale() to add a language to the listing, or register a global locale");
                    }
                    $this->tableName = "object_localized_" . $this->model->getClassId() . "_" . $language;
                }
            }
        }

        return $this->tableName;
    }


    /**
     * @param string $defaultString
     * @param string $column
     * @return string
     */
    protected function getSelectPart($defaultString = "", $column = "oo_id")
    {
        $selectPart = $defaultString;
        $fieldCollections = $this->model->getFieldCollections();
        if (!empty($fieldCollections)) {
            $selectPart = "DISTINCT " . $column;
        }

        return $selectPart;
    }


    /**
     * @param \Zend_DB_Select $select
     *
     * @return $this
     */
    protected function addJoins(\Zend_DB_Select $select)
    {
        // add fielcollection's
        $fieldCollections = $this->model->getFieldCollections();
        if (!empty($fieldCollections)) {
            foreach ($fieldCollections as $fc) {

                // join info
                $table = 'object_collection_' . $fc['type'] . '_' . $this->model->getClassId();
                $name = $fc['type'];
                if (!empty($fc['fieldname'])) {
                    $name .= "~" . $fc['fieldname'];
                }


                // set join condition
                $condition = <<<CONDITION
1
 AND {$this->db->quoteIdentifier($name)}.o_id = {$this->db->quoteIdentifier($this->getTableName())}.o_id
CONDITION;

                if (!empty($fc['fieldname'])) {
                    $condition .= <<<CONDITION
 AND {$this->db->quoteIdentifier($name)}.fieldname = "{$fc['fieldname']}"
CONDITION;
                }


                // add join
                $select->joinLeft(
                    [ $name => $table ], $condition, ''
                );
            }
        }


        // add brick's
        $objectbricks = $this->model->getObjectbricks();
        if (!empty($objectbricks)) {
            foreach ($objectbricks as $ob) {

                // join info
                $table = 'object_brick_query_' . $ob . '_' . $this->model->getClassId();
                $name = $ob;


                // add join
                $select->joinLeft(
                    [ $name => $table ], <<<CONDITION
1
AND {$this->db->quoteIdentifier($name)}.o_id = {$this->db->quoteIdentifier($this->getTableName())}.o_id
CONDITION
                    , ''
                );
            }
        }


        return $this;
    }

    /**
     * @param callable $callback
     */
    public function onCreateQuery(callable $callback)
    {
        $this->onCreateQueryCallback = $callback;
    }
}
