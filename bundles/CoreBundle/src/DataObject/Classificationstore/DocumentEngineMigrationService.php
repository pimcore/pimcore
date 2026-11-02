<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\DataObject\Classificationstore;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EavEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\Service as ClassificationstoreService;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Throwable;

/**
 * The route that moves the stored values of a classification store from the EAV tables to the
 * document engine, in the four steps the four commands of the same names drive.
 *
 * *flip* puts a store on the document engine and creates the document table of every class that
 * carries a field on it, so that the first save after the flip has a table to write to. Everything
 * the store holds is still in the EAV tables at that point, and is read through to them by
 * {@see DocumentEngine::load()} until it is converted - which is what makes the flip reversible by
 * nothing but a drain, and what lets reads and writes of a class keep working while it is running.
 *
 * *drain* converts one (objectId, fieldname) pair at a time: it reads the EAV side with inheritance
 * off, writes the documents if the pair holds none, reads them back, compares them against the EAV
 * side under the exclusions of {@see self::compare()} and only then removes the pair's EAV rows. All
 * of it is one transaction, so a pair is either wholly converted or wholly left on EAV.
 *
 * *verify* recomputes the checksum of every document row and asserts that no EAV row survived for a
 * field name on the document engine; *cleanup* drops the two EAV tables of a class once every store
 * it uses is on the document engine and the verification holds.
 *
 * @internal
 */
final class DocumentEngineMigrationService
{
    private const DOCUMENT_ENGINE = 'document';

    /**
     * The name a store with no id gets in a report: a classification store field that carries no
     * store at all is on the default engine, and names no row an operator could flip.
     */
    private const DEFAULT_STORE_NAME = '(the default store)';

    public function __construct(
        private readonly EngineResolver $engineResolver,
        private readonly DocumentEngine $documentEngine,
        private readonly EavEngine $eavEngine,
        private readonly Connection $db
    ) {
    }

    /**
     * Resolves the store a command was given, by id first and by name second.
     */
    public function resolveStore(string $store): ?StoreConfig
    {
        if (ctype_digit($store)) {
            $byId = StoreConfig::getById((int) $store);
            if ($byId) {
                return $byId;
            }
        }

        return StoreConfig::getByName($store);
    }

    /**
     * Puts the store on the document engine and creates the document table of every class carrying
     * a classification store field on it.
     *
     * Writing the engine column alone would leave the first save after the flip without a table:
     * the tables of an engine are created by a class save, and nothing saves a class because a
     * store changed.
     *
     * @return string[] the ids of the classes whose document table was created
     *
     * @throws Exception
     */
    public function flip(StoreConfig $store): array
    {
        $store->setEngine(self::DOCUMENT_ENGINE);
        $store->save();

        $storeId = (int) $store->getId();
        $classIds = [];

        foreach ($this->loadClassDefinitions() as $class) {
            if (!$this->carriesStore($class, $storeId)) {
                continue;
            }

            $this->documentEngine->createUpdateTables($class, $storeId);
            $classIds[] = $class->getId();
        }

        return $classIds;
    }

    /**
     * Creates the document table of a class, for a drain of a store that was flipped by hand.
     */
    public function createDocumentTable(ClassDefinition $class): void
    {
        $this->documentEngine->createUpdateTables($class, null);
    }

    public function getClassDefinition(string $classId): ?ClassDefinition
    {
        return ClassDefinition::getByIdIgnoreCase($classId);
    }

    /**
     * The classes a per-class command runs over: the one it names, or every class there is. Empty
     * for a class id that resolves to nothing, which is what lets the caller report that.
     *
     * @return ClassDefinition[]
     */
    public function getClasses(?string $classId): array
    {
        if ($classId === null) {
            return $this->loadClassDefinitions();
        }

        return array_filter([$this->getClassDefinition($classId)]);
    }

    /**
     * The field names of a class whose classification store field sits on a store the resolver maps
     * to the document engine.
     *
     * A field name identifies a group of definitions rather than one: the value tables key their
     * rows by (object, fieldname), so two containers defining the field share its rows and are
     * resolved together.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function getDocumentFieldNames(ClassDefinition $class): array
    {
        $fieldNames = [];

        foreach ($this->groupStoreIdsByFieldName($class) as $fieldName => $storeIds) {
            if ($this->engineResolver->resolveForStores($storeIds) === $this->documentEngine) {
                $fieldNames[] = $fieldName;
            }
        }

        return $fieldNames;
    }

    /**
     * Every field name of a class that stores classification store values, whichever engine it is
     * on and whichever container defines it.
     *
     * @return string[]
     */
    public function getClassificationStoreFieldNames(ClassDefinition $class): array
    {
        return array_keys($this->groupStoreIdsByFieldName($class));
    }

    /**
     * The stores of a class that are not on the document engine, which is what keeps its EAV tables
     * in use.
     *
     * The flip is per store while the cleanup is per class, so a class carrying a second field on a
     * store nobody has flipped still needs both tables - and an operator can only act on that if
     * the refusal names the store.
     *
     * @return array<int, string> store id => store name
     *
     * @throws Exception
     */
    public function getStoresBlockingCleanup(ClassDefinition $class): array
    {
        $blocking = [];

        foreach ($this->groupStoreIdsByFieldName($class) as $storeIds) {
            foreach ($storeIds as $storeId) {
                if ($this->engineResolver->resolveForStore($storeId) === $this->documentEngine) {
                    continue;
                }

                $blocking[(int) $storeId] = $this->nameStore($storeId);
            }
        }

        return $blocking;
    }

    /**
     * The backlog of every class that still holds EAV rows for a field name on the document engine,
     * smallest class first - so the smallest one is the canary of a run over a whole installation.
     *
     * @param string|null $classId restricts the run to one class
     *
     * @return array<string, array<array{0: int, 1: string}>> class id => its (objectId, fieldname) pairs
     *
     * @throws Exception
     */
    public function getBacklogByClass(?string $classId = null): array
    {
        $backlog = [];

        foreach ($this->getClasses($classId) as $class) {
            $pairs = $this->getBacklog($class);
            if ($pairs !== []) {
                $backlog[$class->getId()] = $pairs;
            }
        }

        uasort($backlog, static fn (array $first, array $second): int => count($first) <=> count($second));

        return $backlog;
    }

    /**
     * The (objectId, fieldname) pairs the EAV tables of a class still hold for a field name whose
     * store is on the document engine.
     *
     * Both tables are asked: a data row whose group was never activated is read by neither engine
     * and is still an EAV row the verification finds. Neither table carries `fieldname` as the
     * leading column of an index, so this is a scan - which is acceptable for a one-off migration,
     * and is the reason the drain reports the rate it measures rather than promising one.
     *
     * @return array<array{0: int, 1: string}>
     *
     * @throws Exception
     */
    public function getBacklog(ClassDefinition $class): array
    {
        $fieldNames = $this->getDocumentFieldNames($class);
        if ($fieldNames === []) {
            return [];
        }

        $classId = $class->getId();
        $pairs = [];

        foreach ($this->getEavTableNames($classId) as $table) {
            foreach ($this->readPairs($table, $fieldNames) as $pair) {
                $pairs[$pair[0] . "\0" . $pair[1]] = $pair;
            }
        }

        return array_values($pairs);
    }

    /**
     * Converts one (objectId, fieldname) pair, reading with inheritance off so that a parent's
     * values are never written onto a child's document.
     *
     * @throws Exception
     */
    public function drainPair(ClassDefinition $class, int $objectId, string $fieldname, int $migratedAt): DrainPairResult
    {
        return DataObjectService::useInheritedValues(
            false,
            fn (): DrainPairResult => $this->convertPair($class, $objectId, $fieldname, $migratedAt)
        );
    }

    /**
     * Recomputes the checksum of every document row of a class and asserts that no EAV row survived
     * for a field name on the document engine.
     *
     * @throws Exception
     */
    public function verify(ClassDefinition $class): VerificationReport
    {
        [$failures, $unproven] = $this->verifyChecksums($class->getId());

        foreach ($this->getBacklog($class) as [$objectId, $fieldname]) {
            $failures[] = sprintf(
                'object %d, field "%s": EAV rows survive for a field on the document engine',
                $objectId,
                $fieldname
            );
        }

        return new VerificationReport($failures, $unproven);
    }

    /**
     * Drops the two EAV tables of a class, or names the ones a dry run would drop.
     *
     * @return string[] the tables that were dropped, or that would be
     *
     * @throws Exception
     */
    public function dropEavTables(ClassDefinition $class, bool $dryRun): array
    {
        $tables = [];

        foreach ($this->getEavTableNames($class->getId()) as $table) {
            if ($this->tableExists($table)) {
                $tables[] = $table;
            }
        }

        if (!$dryRun && $tables !== []) {
            $this->eavEngine->dropTables($class);
        }

        return $tables;
    }

    /**
     * @throws Exception
     */
    private function convertPair(
        ClassDefinition $class,
        int $objectId,
        string $fieldname,
        int $migratedAt
    ): DrainPairResult {
        $classId = $class->getId();
        $this->db->beginTransaction();

        try {
            $result = $this->convertPairInTransaction($class, $classId, $objectId, $fieldname, $migratedAt);

            // the document of a diverging pair goes with the transaction, so the pair stays wholly
            // on EAV and the read-through keeps serving its stored values
            if ($result->outcome === DrainOutcome::Diverged) {
                $this->db->rollBack();

                return $result;
            }

            $this->eavEngine->deletePairRows($classId, $objectId, $fieldname);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->isTransactionActive()) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function convertPairInTransaction(
        ClassDefinition $class,
        string $classId,
        int $objectId,
        string $fieldname,
        int $migratedAt
    ): DrainPairResult {
        // a user save that converted the pair while the drain was running owns it: its document is
        // what the pair is read from, so nothing is written and nothing is compared
        if ($this->documentEngine->hasDocuments($classId, $objectId, $fieldname)) {
            return new DrainPairResult(DrainOutcome::AlreadyMigrated, $objectId, $fieldname);
        }

        // the value tables carry a foreign key on `objects`, which a folder satisfies as well, so a
        // row can point at an id no concrete object answers - which is a divergence rather than an
        // error: the rows stay where they are and are reported with the id that holds them
        $object = Concrete::getById($objectId, ['force' => true]);
        if ($object === null) {
            return new DrainPairResult(
                DrainOutcome::Diverged,
                $objectId,
                $fieldname,
                'no concrete object with this id exists'
            );
        }

        $eavStore = $this->readStore($object, $class, $fieldname, $this->eavEngine->load(...));

        // the write-if-absent guard answers again under the lock it takes, so a save that committed
        // between the check above and here is the one that owns the pair
        if (!$this->documentEngine->writeMigratedDocuments($eavStore, $migratedAt)) {
            return new DrainPairResult(DrainOutcome::AlreadyMigrated, $objectId, $fieldname);
        }

        $documentStore = $this->readStore($object, $class, $fieldname, $this->documentEngine->load(...));

        $divergence = $this->compare($eavStore, $documentStore, $classId, $objectId, $fieldname);
        if ($divergence !== null) {
            return new DrainPairResult(DrainOutcome::Diverged, $objectId, $fieldname, $divergence);
        }

        return new DrainPairResult(DrainOutcome::Converted, $objectId, $fieldname);
    }

    /**
     * @param callable(Classificationstore): void $load
     *
     * @throws Exception
     */
    private function readStore(
        Concrete $object,
        ClassDefinition $class,
        string $fieldname,
        callable $load
    ): Classificationstore {
        $classificationStore = new Classificationstore();
        $classificationStore->setObject($object);
        $classificationStore->setClass($class);
        $classificationStore->setFieldname($fieldname);

        $load($classificationStore);

        return $classificationStore;
    }

    /**
     * Whether the written documents give back the values the EAV rows hold, under the five
     * exclusions of the value migration specification.
     *
     * The comparison is on the denormalised model values rather than on the rows, which is what
     * makes three of the five structural rather than a rule of their own: the `type` column of a
     * data row has no counterpart in a document, a collection placeholder row carries no value and
     * is not stored in the model at all - {@see Classificationstore::setLocalizedKeyValue()} drops
     * a null - and a row whose key resolves to no field definition is skipped by
     * {@see EavEngine::load()}, so it reaches neither side. The two that do need a rule are the
     * collection mappings, {@see self::compareMappings()}.
     *
     * @return string|null the reason the pair diverged, or null when it compares equal
     *
     * @throws Exception
     */
    private function compare(
        Classificationstore $eavStore,
        Classificationstore $documentStore,
        string $classId,
        int $objectId,
        string $fieldname
    ): ?string {
        $storedGroups = array_keys($eavStore->getActiveGroups());
        $readGroups = array_keys($documentStore->getActiveGroups());
        sort($storedGroups);
        sort($readGroups);

        if ($storedGroups !== $readGroups) {
            return sprintf(
                'the active groups differ: stored [%s], read back [%s]',
                implode(',', $storedGroups),
                implode(',', $readGroups)
            );
        }

        return $this->compareItems($eavStore, $documentStore)
            ?? $this->compareMappings($eavStore, $documentStore, $classId, $objectId, $fieldname);
    }

    private function compareItems(Classificationstore $eavStore, Classificationstore $documentStore): ?string
    {
        $storedItems = $eavStore->getItems();
        $readItems = $documentStore->getItems();

        foreach ($this->getItemAddresses($storedItems, $readItems) as [$groupId, $keyId, $language]) {
            $stored = $storedItems[$groupId][$keyId][$language] ?? null;
            $read = $readItems[$groupId][$keyId][$language] ?? null;

            if (!$this->holdsTheSameValue($stored, $read)) {
                return sprintf(
                    'group %d, key %d, language "%s" does not come back as it was stored',
                    $groupId,
                    $keyId,
                    $language
                );
            }
        }

        return null;
    }

    /**
     * The collection mappings, under the two exclusions that are not structural.
     *
     * A group the EAV rows map to no collection is not recordable in a document: an object's
     * mappings and its inheritable parent's are merged as an array union, so a null entry on the
     * child would shadow the collection the parent maps. Neither is a group whose EAV rows disagree
     * on a collection id among themselves - a document holds one id per group and cannot carry two.
     * And a mapping the inheritable parent already answers is deliberately left out of the child's
     * own rows, {@see DocumentEngine::getCollectionMappingToStore()}, so the document answers it
     * through inheritance rather than from the row.
     *
     * @throws Exception
     */
    private function compareMappings(
        Classificationstore $eavStore,
        Classificationstore $documentStore,
        string $classId,
        int $objectId,
        string $fieldname
    ): ?string {
        $storedMappings = $this->normaliseMappings($eavStore->getGroupCollectionMappings());
        $readMappings = $this->normaliseMappings($documentStore->getGroupCollectionMappings());
        $ambiguous = $this->getGroupsWithAmbiguousCollection($classId, $objectId, $fieldname);
        $inheritedMappings = null;

        foreach (array_keys($storedMappings + $readMappings) as $groupId) {
            $stored = $storedMappings[$groupId] ?? null;

            if ($stored === null || isset($ambiguous[$groupId])) {
                continue;
            }

            if (($readMappings[$groupId] ?? null) === $stored) {
                continue;
            }

            $inheritedMappings ??= $this->normaliseMappings(
                DataObjectService::useInheritedValues(true, $documentStore->getGroupCollectionMappings(...))
            );

            if (($inheritedMappings[$groupId] ?? null) !== $stored) {
                return sprintf('group %d does not come back on the collection it was stored with', $groupId);
            }
        }

        return null;
    }

    /**
     * The groups whose value rows do not agree on one collection id. A null counts as a value of
     * its own here: a group holding one row with a collection and one without disagrees as much as
     * one holding two different collections.
     *
     * @return array<int, bool>
     *
     * @throws Exception
     */
    private function getGroupsWithAmbiguousCollection(string $classId, int $objectId, string $fieldname): array
    {
        try {
            $groupIds = $this->db->fetchFirstColumn(
                'SELECT `groupId` FROM ' . $this->db->quoteIdentifier($this->eavEngine->getDataTableName($classId))
                . ' WHERE `id` = ? AND `fieldname` = ? GROUP BY `groupId`'
                . ' HAVING COUNT(DISTINCT COALESCE(`collectionId`, -1)) > 1',
                [$objectId, $fieldname]
            );
        } catch (TableNotFoundException) {
            return [];
        }

        return array_fill_keys(array_map(intval(...), $groupIds), true);
    }

    /**
     * Every (groupId, keyId, language) either side holds a value under.
     *
     * @param array<int, array<int, array<string, mixed>>> $storedItems
     * @param array<int, array<int, array<string, mixed>>> $readItems
     *
     * @return array<array{0: int, 1: int, 2: string}>
     */
    private function getItemAddresses(array $storedItems, array $readItems): array
    {
        $addresses = [];

        foreach ([$storedItems, $readItems] as $items) {
            foreach ($items as $groupId => $keys) {
                foreach ($keys as $keyId => $languages) {
                    foreach (array_keys($languages) as $language) {
                        $addresses[$groupId . "\0" . $keyId . "\0" . $language] = [
                            (int) $groupId, (int) $keyId, (string) $language,
                        ];
                    }
                }
            }
        }

        return array_values($addresses);
    }

    /**
     * Two denormalised values hold the same thing.
     *
     * They are compared by value rather than identically, which is the comparison
     * {@see \Pimcore\Tests\Model\DataType\ClassificationStore\DocumentEngineRoundTripTest} already
     * holds the two engines to: both sides come back through the same field definitions, but a
     * value that travelled through JSON and one that travelled through a LONGTEXT column can differ
     * in their PHP type without differing in what they hold, and a drain that halted over that
     * would halt over nothing. A value that is null on exactly one side is a divergence all the
     * same - that is the shape a lost value has.
     */
    private function holdsTheSameValue(mixed $stored, mixed $read): bool
    {
        if (($stored === null) !== ($read === null)) {
            return false;
        }

        return $stored == $read;
    }

    /**
     * @param array<int|string, mixed> $mappings
     *
     * @return array<int, int|null>
     */
    private function normaliseMappings(array $mappings): array
    {
        $normalised = [];

        foreach ($mappings as $groupId => $collectionId) {
            $normalised[(int) $groupId] = $collectionId === null ? null : (int) $collectionId;
        }

        return $normalised;
    }

    /**
     * @return array{0: string[], 1: string[]} the failing rows and the unproven ones
     *
     * @throws Exception
     */
    private function verifyChecksums(string $classId): array
    {
        $failures = [];
        $unproven = [];

        foreach ($this->readDocumentRows($classId) as $row) {
            $where = sprintf(
                'object %d, field "%s", group %d',
                (int) $row['id'],
                (string) $row['fieldname'],
                (int) $row['groupId']
            );

            if ($row['checksum'] === null) {
                // a document written before any writer stamped a checksum carries no evidence
                // either way, so it is reported rather than called corrupt
                if ($row['migratedAt'] === null) {
                    $unproven[] = $where . ': written before checksums were stamped';
                } else {
                    $failures[] = $where . ': a drained document carries no checksum';
                }

                continue;
            }

            if (!hash_equals((string) $row['checksum'], sha1((string) $row['values']))) {
                $failures[] = $where . ': the stored checksum does not match its values';
            }
        }

        return [$failures, $unproven];
    }

    /**
     * @return array<array<string, mixed>>
     *
     * @throws Exception
     */
    private function readDocumentRows(string $classId): array
    {
        try {
            return $this->db->fetchAllAssociative(
                'SELECT `id`, `fieldname`, `groupId`, `values`, `checksum`, `migratedAt` FROM '
                . $this->db->quoteIdentifier($this->documentEngine->getDocumentTableName($classId))
                . ' ORDER BY `id`, `fieldname`, `groupId`'
            );
        } catch (TableNotFoundException) {
            return [];
        }
    }

    /**
     * @param string[] $fieldNames
     *
     * @return array<array{0: int, 1: string}>
     *
     * @throws Exception
     */
    private function readPairs(string $table, array $fieldNames): array
    {
        try {
            $rows = $this->db->fetchAllAssociative(
                'SELECT DISTINCT `id`, `fieldname` FROM ' . $this->db->quoteIdentifier($table)
                . ' WHERE `fieldname` IN (?) ORDER BY `id`, `fieldname`',
                [$fieldNames],
                [ArrayParameterType::STRING]
            );
        } catch (TableNotFoundException) {
            return [];
        }

        return array_map(
            static fn (array $row): array => [(int) $row['id'], (string) $row['fieldname']],
            $rows
        );
    }

    /**
     * @return string[] the value table before the group activation table, which is the order the
     *                  composite foreign key between them asks for
     */
    private function getEavTableNames(string $classId): array
    {
        return [$this->eavEngine->getDataTableName($classId), $this->eavEngine->getGroupsTableName($classId)];
    }

    /**
     * @throws Exception
     */
    private function tableExists(string $table): bool
    {
        return $this->db->fetchOne('SHOW TABLES LIKE ?', [$table]) !== false;
    }

    /**
     * @return array<string, array<string, int|null>> field name => (qualified identity => store id)
     */
    private function groupStoreIdsByFieldName(ClassDefinition $class): array
    {
        return ClassificationstoreService::groupStoreIdsByFieldName(
            ClassificationstoreService::getClassificationStoreFieldDefinitions($class->getFieldDefinitions())
        );
    }

    private function carriesStore(ClassDefinition $class, int $storeId): bool
    {
        foreach ($this->groupStoreIdsByFieldName($class) as $storeIds) {
            if (in_array($storeId, array_map(intval(...), $storeIds), true)) {
                return true;
            }
        }

        return false;
    }

    private function nameStore(?int $storeId): string
    {
        if ($storeId === null) {
            return self::DEFAULT_STORE_NAME;
        }

        return (string) (StoreConfig::getById($storeId)?->getName() ?? self::DEFAULT_STORE_NAME);
    }

    /**
     * @return ClassDefinition[]
     */
    private function loadClassDefinitions(): array
    {
        return (new ClassDefinitionListing())->load();
    }
}
