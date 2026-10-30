<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Exception;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * The name of the EAV value table of the model's class, whether or not that engine is the one
     * behind the model's store.
     *
     * The method predates the engines and is part of the published facade, so it keeps its name and
     * its arity. It names a table rather than asking an engine for one: a store on the document
     * engine keeps its values in `object_classificationstore_doc_<CLASS>` and has no table of this
     * name at all. Read the store's engine to find out which storage is in use.
     *
     * @deprecated and will be removed in Pimcore 2027.1. It answers for one engine only.
     */
    public function getDataTableName(): string
    {
        return 'object_classificationstore_data_' . $this->model->getClass()->getId();
    }

    /**
     * The name of the EAV group table of the model's class, under the same caveat as
     * {@see self::getDataTableName()}: the document engine has no group table, as the presence of a
     * value row is what makes a group active there.
     *
     * @deprecated and will be removed in Pimcore 2027.1. It answers for one engine only.
     */
    public function getGroupsTableName(): string
    {
        return 'object_classificationstore_groups_' . $this->model->getClass()->getId();
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $this->getEngine()->save($this->model);
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $this->getEngine()->delete($this->model);
    }

    /**
     * @throws Exception
     */
    public function load(): void
    {
        $this->getEngine()->load($this->model);
    }

    /**
     * Kept as the published facade of the model; the plural method is on the engine interface.
     *
     * classSaved() no longer comes through here - it routes from the field definition it was
     * dispatched on, which knows its own store - so this is the path of third-party callers that
     * have only the model, which names a field rather than a definition. The engine is the one of
     * the whole group, and the store handed to it the first of them, as a group that does not agree
     * on its engine is refused before this point.
     *
     * @throws Exception
     */
    public function createUpdateTable(): void
    {
        $storeIds = $this->getStoreIds();
        $storeId = $storeIds === [] ? null : reset($storeIds);

        $this->getEngineResolver()->resolveForStores($storeIds)
            ->createUpdateTables($this->model->getClass(), $storeId);
    }

    /**
     * @throws Exception
     */
    private function getEngine(): EngineInterface
    {
        return $this->getEngineResolver()->resolveForStores($this->getStoreIds());
    }

    private function getEngineResolver(): EngineResolver
    {
        return Pimcore::getContainer()->get(EngineResolver::class);
    }

    /**
     * The configured stores of every field definition the model's fieldname reaches.
     *
     * Two object bricks allowed on the same class may define a classification store field under the
     * same name, so the name identifies a group rather than one definition. The value tables key
     * their rows by (object, fieldname), so such fields share their rows - the resolver is what
     * refuses the group when they do not share an engine.
     *
     * Empty when the model carries no fieldname, or when no definition matches it, which both mean
     * the default engine.
     *
     * @return array<string, int|null> container-qualified field identity => store id
     */
    private function getStoreIds(): array
    {
        // createUpdateTable() is reachable on a model that carries only its class, with no fieldname
        $fieldname = $this->model->getObjectVars()['fieldname'] ?? null;
        if (!is_string($fieldname)) {
            return [];
        }

        $classificationStoreFields = Service::getClassificationStoreFieldDefinitions(
            $this->model->getClass()->getFieldDefinitions()
        );

        return Service::groupStoreIdsByFieldName($classificationStoreFields)[$fieldname] ?? [];
    }
}
