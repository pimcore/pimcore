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

use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\ParameterType;
use Exception;
use Generator;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service as ElementService;
use Throwable;

/**
 * The route that moves the stored values of a classification store from the EAV tables to the
 * document engine, in the four steps the four commands of the same names drive.
 *
 * *flip* creates the document table of every class that carries a field on the store and only then
 * puts the store on the document engine, so that the first save after the flip has a table to write
 * to and no process can see the switch before it. Everything the store holds is still in the EAV
 * tables at that point, and is read through to them by {@see DocumentEngine::load()} until it is
 * converted - which is what makes the flip reversible by nothing but a drain, and what lets reads
 * and writes of a class keep working while it is running.
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

    /**
     * Document rows the verification holds in memory at once. Each one carries a whole group's
     * stored values, so this is what bounds {@see self::verifyChecksums()} on a class whose data
     * does not fit in the process.
     */
    private const VERIFY_BATCH_SIZE = 100;

    /**
     * (objectId, fieldname) pairs the backlog reads from a value table at a time. The table is
     * walked along the leading columns of its primary key, so what the drain holds in memory is one
     * batch of pairs rather than the backlog of the class. One batch is one statement and a hundred
     * transactions, so there is nothing to be gained by making it large.
     */
    private const BACKLOG_BATCH_SIZE = 100;

    /**
     * The order the pairs of a class are walked in, which is the order of the leading columns of the
     * primary key of both value tables.
     */
    private const ORDER_BY_PAIR = ' ORDER BY `id`, `fieldname`';

    /**
     * How deep {@see self::holdsTheSameValue()} walks into a value before it stops calling the two
     * sides equal. A denormalised value is a few levels at most - an array of rows, a quantity value
     * and its unit - while a model object reached through one carries a back reference to its own
     * DAO, which is a cycle. Running out of depth is reported as a divergence rather than as
     * equality: that is the direction the drain may be wrong in.
     */
    private const MAX_COMPARISON_DEPTH = 8;

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
     * Puts the given stores on the document engine and creates the document table of every class
     * carrying a classification store field on one of them.
     *
     * Writing the engine column alone would leave the first save after the flip without a table:
     * the tables of an engine are created by a class save, and nothing saves a class because a
     * store changed. The tables are therefore created *before* the engine column is written, in
     * this order for two reasons: every other process reads the engine column, so a save that saw
     * the flip before its table existed would fail with table-not-found, and a DDL that failed
     * half way would otherwise leave the store switched onto tables that are not there. Creating
     * the table of a store that is then not flipped leaves an empty table behind and nothing else.
     *
     * More than one store is taken because a field-name group spanning two stores has to go over
     * in one step, {@see self::findFlipConflicts()}. The engine columns are written in one
     * transaction, so no other connection sees such a group half flipped.
     *
     * @return string[] the ids of the classes whose document table was created
     *
     * @throws StoreFlipConflictException
     * @throws Exception
     */
    public function flip(StoreConfig ...$stores): array
    {
        $storeIds = array_map(static fn (StoreConfig $store): int => (int) $store->getId(), $stores);
        $classes = $this->loadClassDefinitions();

        $conflicts = $this->findFlipConflicts($classes, $storeIds);
        if ($conflicts !== []) {
            throw new StoreFlipConflictException($conflicts);
        }

        $classIds = [];

        foreach ($classes as $class) {
            $carried = array_values(array_filter(
                $storeIds,
                fn (int $storeId): bool => $this->carriesStore($class, $storeId)
            ));

            if ($carried === []) {
                continue;
            }

            foreach ($carried as $storeId) {
                $this->documentEngine->createUpdateTables($class, $storeId);
            }

            $classIds[] = $class->getId();
        }

        $this->db->transactional(static function () use ($stores): void {
            foreach ($stores as $store) {
                $store->setEngine(self::DOCUMENT_ENGINE);
                $store->save();
            }
        });

        return $classIds;
    }

    /**
     * The field-name groups a flip of exactly these stores would leave split across two engines.
     *
     * A field name identifies a group of definitions rather than one - two allowed bricks of the
     * same class can define the same classification store field on two different stores - and the
     * value tables key their rows by (object, fieldname), so the group has to agree on one engine.
     * Flipping one store of such a group would report success and then have every load, save and
     * drain of that field refused by {@see EngineResolver::resolveForStores()}.
     *
     * A store of the group that is already on the document engine is no conflict, and neither is
     * one the same flip is taking over - which is what lets a group that spans two stores go over,
     * and is why the refusal names the stores rather than only the field.
     *
     * @param ClassDefinition[] $classes
     * @param int[]             $storeIds the stores this flip would put on the document engine
     *
     * @return string[] one line per group, naming the class, the field and the stores in the way
     *
     * @throws Exception
     */
    private function findFlipConflicts(array $classes, array $storeIds): array
    {
        $conflicts = [];

        foreach ($classes as $class) {
            foreach ($this->groupStoreIdsByFieldName($class) as $fieldName => $groupStoreIds) {
                $blocking = $this->findBlockingStores($groupStoreIds, $storeIds);

                if ($blocking !== []) {
                    $conflicts[] = sprintf(
                        'class "%s", field "%s" is also defined on %s',
                        $class->getId(),
                        $fieldName,
                        implode(' and ', $blocking)
                    );
                }
            }
        }

        return $conflicts;
    }

    /**
     * The stores of one field-name group that the flip would leave behind on another engine, or an
     * empty array when the group does not touch the flip at all.
     *
     * @param array<string, int|null> $groupStoreIds container-qualified field identity => store id
     * @param int[]                   $storeIds
     *
     * @return string[] the blocking stores, named
     *
     * @throws Exception
     */
    private function findBlockingStores(array $groupStoreIds, array $storeIds): array
    {
        $ids = array_map(static fn (int|string|null $id): ?int => $id === null ? null : (int) $id, $groupStoreIds);
        $goingOver = static fn (?int $id): bool => $id !== null && in_array($id, $storeIds, true);

        if (array_filter($ids, $goingOver) === []) {
            return [];
        }

        $blocking = [];

        foreach ($ids as $id) {
            if ($goingOver($id) || $this->engineResolver->resolveForStore($id) === $this->documentEngine) {
                continue;
            }

            $blocking[(string) ($id ?? 'default')] = $this->describeStore($id);
        }

        return array_values($blocking);
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
     * How many pairs every class that still holds EAV rows for a field name on the document engine
     * has left, smallest class first - so the smallest one is the canary of a run over a whole
     * installation.
     *
     * A count rather than the pairs themselves: the order a run goes in has to be known before it
     * starts, and reading every pending pair of every class to work it out is what would exhaust a
     * large installation before it converted anything. The pairs of one class are then streamed,
     * {@see self::streamBacklog()}.
     *
     * @param string|null $classId restricts the run to one class
     *
     * @return array<string, int> class id => its pending (objectId, fieldname) pairs
     *
     * @throws Exception
     */
    public function getBacklogSizeByClass(?string $classId = null): array
    {
        $sizes = [];

        foreach ($this->getClasses($classId) as $class) {
            $size = $this->countBacklog($class);
            if ($size > 0) {
                $sizes[$class->getId()] = $size;
            }
        }

        asort($sizes);

        return $sizes;
    }

    /**
     * How many (objectId, fieldname) pairs the EAV tables of a class still hold for a field name
     * whose store is on the document engine.
     *
     * @throws Exception
     */
    public function countBacklog(ClassDefinition $class): int
    {
        $fieldNames = $this->getDocumentFieldNames($class);
        if ($fieldNames === []) {
            return 0;
        }

        // no batch limit: this counts the whole backlog, which is one pass over each table and one
        // number out of it
        $union = $this->buildPairUnion($class->getId(), $fieldNames, null, null);
        if ($union === null) {
            return 0;
        }

        [$sql, $parameters, $types] = $union;

        return (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM (' . $sql . ') pairs',
            $parameters,
            $types
        );
    }

    /**
     * The (objectId, fieldname) pairs the EAV tables of a class still hold for a field name whose
     * store is on the document engine, a bounded batch at a time.
     *
     * Both tables are asked, as the one union {@see self::countBacklog()} counts, so that a pair
     * both of them hold is one pair here as well. Each is walked along the leading columns of its
     * primary key, `(id, fieldname)`: a batch is an index range from where the previous one ended
     * rather than a fresh scan, and what is held in memory is that batch instead of the backlog of
     * the class. The `fieldname` filter is not indexed, which is why the drain reports the rate it
     * measures rather than promising one.
     *
     * The cursor only moves forward, so a pair the drain converted is not read again and one it
     * left on EAV - a divergence - does not stall the run on itself.
     *
     * @return Generator<int, array{0: int, 1: string}>
     *
     * @throws Exception
     */
    public function streamBacklog(ClassDefinition $class): Generator
    {
        $fieldNames = $this->getDocumentFieldNames($class);
        if ($fieldNames === []) {
            return;
        }

        $after = null;

        while (($pairs = $this->readPairs($class->getId(), $fieldNames, $after)) !== []) {
            yield from $pairs;

            $after = $pairs[array_key_last($pairs)];
        }
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
     * What it found is reported as it is found: a store that was never checksummed has one message
     * per row of the class, which is as many messages as the class has stored groups, and keeping
     * them to hand back at the end would make the report grow with the data it is a report about -
     * on exactly the installations the batching of {@see self::readDocumentRows()} is for. The
     * caller is handed each finding and the report carries the counts alone, which is all the
     * cleanup needs of it.
     *
     * @param (callable(VerificationFinding, string): void)|null $onFinding
     *
     * @throws Exception
     */
    public function verify(ClassDefinition $class, ?callable $onFinding = null): VerificationReport
    {
        $onFinding ??= static function (): void {
        };

        $counts = [VerificationFinding::Failure->name => 0, VerificationFinding::Unproven->name => 0];

        $record = static function (VerificationFinding $finding, string $message) use (
            $onFinding,
            &$counts
        ): void {
            $counts[$finding->name]++;
            $onFinding($finding, $message);
        };

        $this->verifyChecksums($class->getId(), $record);

        foreach ($this->streamBacklog($class) as [$objectId, $fieldname]) {
            $record(VerificationFinding::Failure, sprintf(
                'object %d, field "%s": EAV rows survive for a field on the document engine',
                $objectId,
                $fieldname
            ));
        }

        return new VerificationReport(
            $counts[VerificationFinding::Failure->name],
            $counts[VerificationFinding::Unproven->name]
        );
    }

    /**
     * Drops the two EAV tables of a class, or names the ones a dry run would drop.
     *
     * The DROP waits for every read-through that may still read those tables: such a read holds
     * their metadata locks for the length of its transaction, {@see DocumentEngine::load()}, so a
     * reader whose snapshot predates the last conversion finishes reading the rows that snapshot
     * holds before the tables go - rather than finding its own source gone mid-load.
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
            // on EAV and the read-through keeps serving its stored values. A pair that was already
            // converted and diverges has nothing of this run's to roll back, and its surviving EAV
            // rows are what the rollback keeps - {@see self::reconcileConvertedPair()}
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

        $this->forgetConvertedObject($objectId);

        return $result;
    }

    /**
     * Drops the converted object from the caches that hold it, now that the pair is committed.
     *
     * The conversion moves the rows an already loaded object was built from, and a loaded object is
     * held in two places: the runtime cache, which `AbstractObject::getById()` registers it in even
     * for a forced load, and the element cache any process that loaded it before the drain wrote it
     * to. A classification store is not lazy loaded - `Concrete\Dao::getData()` loads it with the
     * rest of the object - so such an entry carries the values of the side the pair has just left.
     *
     * The two sides do not read a null collection mapping the same way, which is what makes a stale
     * entry wrong rather than merely old: an EAV group mapped to no collection is an entry holding
     * null and shadows the collection an ancestor maps the group to, while the same group's document
     * row carries null precisely so that the ancestor answers it - the two readings of
     * {@see DocumentEngine::getInheritedCollectionMappings()}. A child whose own mapping was
     * therefore left out of its document resolves it through its parent, and would resolve it to the
     * null of a parent still served from one of these caches instead of to the collection it was
     * stored with.
     *
     * Everything tagged with the object goes with it: a cached object carries its own tag,
     * {@see \Pimcore\Model\Element\AbstractElement::getCacheTags()}, as does every entry built over
     * it. That one tag is the whole of what a conversion invalidates, which is where this is
     * narrower than the sweep `AbstractObject::clearDependentCacheByObjectId()` performs after
     * a save: a drain runs over every object of an installation, and nothing it writes touches the
     * properties or the rendered output of one.
     */
    private function forgetConvertedObject(int $objectId): void
    {
        $cacheTag = ElementService::getElementCacheTag('object', $objectId);

        // the cache key of an element is its cache tag
        if (RuntimeCache::isRegistered($cacheTag)) {
            RuntimeCache::getInstance()->offsetUnset($cacheTag);
        }

        Cache::clearTag($cacheTag);
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
        $this->lockObjectRow($objectId);

        // a user save that converted the pair while the drain was running owns it: its document is
        // what the pair is read from, so nothing is written over it. The question is asked under the
        // lock it takes, and before anything else is read, so that this transaction's snapshot is
        // not older than the answer it gave
        $alreadyMigrated = $this->documentEngine->hasDocuments($classId, $objectId, $fieldname);

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

        if ($alreadyMigrated) {
            return $this->reconcileConvertedPair($object, $class, $eavStore, $classId, $objectId, $fieldname);
        }

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
     * Takes the object's row before anything of the document engine is locked, which is the order
     * an ordinary save takes the two in.
     *
     * A save locks the object's row first - {@see \Pimcore\Model\DataObject\AbstractObject::save()}
     * through `getCurrentFullPathForUpdate()` - and only then the pair's document rows, while the
     * conversion below locks the document rows to ask whether the pair is already converted and
     * then needs the object's row for the foreign key of its own insert,
     * {@see DocumentEngine::createUpdateTables()}. Taken in that order the two would deadlock on
     * each other: the save waiting for the document gap the drain holds while the drain waits for
     * the object row the save holds. The statement is the one the save issues, so both sides queue
     * on the same lock and the drain simply waits for the save to finish.
     *
     * This is a locking read, so it establishes no consistent-read snapshot: the question
     * {@see DocumentEngine::hasDocuments()} asks next is still the first read of this transaction
     * that one could be taken from, which is what its own contract requires.
     *
     * @throws Exception
     */
    private function lockObjectRow(int $objectId): void
    {
        $this->db->fetchOne('SELECT `id` FROM `objects` WHERE `id` = ? FOR UPDATE', [$objectId]);
    }

    /**
     * What to do with the EAV rows that survived beside a pair that is already converted.
     *
     * A save on this engine takes the pair's EAV rows in the same transaction as the documents it
     * writes, so a converted pair normally has none left and there is nothing to decide. Rows that
     * are there all the same were written while the pair was already converted - by a process that
     * had resolved the EAV engine before the flip and has not reached its reset boundary yet, which
     * is why the flip asks for those writers to be let finish first.
     *
     * Such rows are removed only when they hold nothing the documents do not, which is asked one
     * way round on purpose: the documents of a converted pair are what it is read from and may
     * hold more than the rows beside them - the rows the conversion took are exactly the ones that
     * are gone, and a group activation row left without values holds nothing either. A value the
     * rows hold and the documents do not is the one thing no drain can resolve: it is reported with
     * its object and field name, the pair is left as it stands and the class halts, rather than the
     * newer value being deleted by the migration.
     *
     * @throws Exception
     */
    private function reconcileConvertedPair(
        Concrete $object,
        ClassDefinition $class,
        Classificationstore $eavStore,
        string $classId,
        int $objectId,
        string $fieldname
    ): DrainPairResult {
        $documentStore = $this->readStore($object, $class, $fieldname, $this->documentEngine->load(...));

        $unaccounted = $this->findUnaccountedValue($eavStore, $documentStore)
            ?? $this->compareMappings($eavStore, $documentStore, $classId, $objectId, $fieldname);

        if ($unaccounted === null) {
            return new DrainPairResult(DrainOutcome::AlreadyMigrated, $objectId, $fieldname);
        }

        return new DrainPairResult(
            DrainOutcome::Diverged,
            $objectId,
            $fieldname,
            'the pair is converted and still holds EAV rows its documents do not account for ('
            . $unaccounted . '), so they were written after it was converted: reconcile the two by'
            . ' hand rather than letting the drain delete them'
        );
    }

    /**
     * The first value the EAV side holds that the document side does not give back the same.
     *
     * Unlike {@see self::compareItems()} this is one-directional: a value only the documents hold
     * is what a conversion leaves behind, not a loss. {@see self::compareMappings()} is already
     * asked this way round and is reused as it stands.
     *
     * @return string|null the address of the value, or null when every one of them is accounted for
     */
    private function findUnaccountedValue(
        Classificationstore $eavStore,
        Classificationstore $documentStore
    ): ?string {
        $readItems = $documentStore->getItems();

        foreach ($eavStore->getItems() as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $stored) {
                    if ($stored === null) {
                        continue;
                    }

                    if ($this->holdsTheSameValue($stored, $readItems[$groupId][$keyId][$language] ?? null)) {
                        continue;
                    }

                    return sprintf(
                        'group %d, key %d, language "%s"',
                        (int) $groupId,
                        (int) $keyId,
                        (string) $language
                    );
                }
            }
        }

        return null;
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
     * own rows, so the document answers it through inheritance rather than from the row - which is
     * asked of {@see DocumentEngine::getInheritedCollectionMappings()} rather than of the model,
     * because the model reaches a getter named after the field and a classification store defined
     * inside a brick or a field collection has none.
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

            // the object's own mappings win over its ancestors', which is the precedence the model
            // merges the two with
            $inheritedMappings ??= $readMappings + $this->normaliseMappings(
                $this->documentEngine->getInheritedCollectionMappings($documentStore)
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
     * The one difference the two storage shapes explain is a type: both sides come back through the
     * same field definitions, but the EAV value columns are LONGTEXT, so a number or a flag arrives
     * from them as the text it was written as while the same value arrives out of a JSON document as
     * the int, float or bool it went in as - and a drain that halted over that would halt over
     * nothing. Two *texts* are held to exact equality all the same: `'00123'` and `'123'` are two
     * different inputs, {@see \Pimcore\Model\DataObject\Traits\SimpleNormalizerTrait} keeps either
     * verbatim, and calling them equal is what would have the reconciliation of a converted pair
     * delete the first while the documents hold the second. A value that is null on exactly one side
     * is a divergence too - that is the shape a lost value has.
     *
     * The rule reaches inside the value rather than only its top level: a multiselect, a table or a
     * quantity value carries its text one or two levels down, where equating two different strings
     * loses exactly as much.
     */
    private function holdsTheSameValue(mixed $stored, mixed $read, int $depth = 0): bool
    {
        if ($stored === $read) {
            return true;
        }

        if ($stored === null || $read === null || $depth >= self::MAX_COMPARISON_DEPTH) {
            return false;
        }

        if (is_array($stored) && is_array($read)) {
            return $this->holdTheSameValues($stored, $read, $depth);
        }

        if (is_object($stored) && is_object($read)) {
            return $this->holdTheSameObject($stored, $read, $depth);
        }

        if (!is_scalar($stored) || !is_scalar($read)) {
            return false;
        }

        // two texts were already asked for identity above, and nothing else may equate them
        if (is_string($stored) && is_string($read)) {
            return false;
        }

        // one side is the text a LONGTEXT column hands back, which is what casting the value the
        // other side holds produces - a float as `12.57`, a flag as `1`
        return (string) $stored === (string) $read;
    }

    /**
     * Two values member by member: the same keys on both sides, and the same value under each of
     * them under {@see self::holdsTheSameValue()}.
     *
     * @param array<array-key, mixed> $stored
     * @param array<array-key, mixed> $read
     */
    private function holdTheSameValues(array $stored, array $read, int $depth): bool
    {
        if (count($stored) !== count($read)) {
            return false;
        }

        foreach ($stored as $key => $value) {
            if (!array_key_exists($key, $read)
                || !$this->holdsTheSameValue($value, $read[$key], $depth + 1)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Two objects of a denormalised value, compared as the values they carry.
     *
     * A date is compared by the instant it stands for, which is what PHP's own comparison of two
     * date objects answers and what the two sides have to agree on - not by the bookkeeping
     * properties a Carbon carries beside it. An element is referenced rather than held, so its
     * identity is the value and its loaded state is nothing the two engines could differ about.
     * Anything else - a quantity value, an rgba colour, an encrypted field - is compared property
     * by property, which is where its text is.
     */
    private function holdTheSameObject(object $stored, object $read, int $depth): bool
    {
        if ($stored instanceof DateTimeInterface && $read instanceof DateTimeInterface) {
            return $stored == $read;
        }

        if ($stored::class !== $read::class) {
            return false;
        }

        if ($stored instanceof ElementInterface && $read instanceof ElementInterface) {
            return $stored->getId() === $read->getId();
        }

        return $this->holdTheSameValues((array) $stored, (array) $read, $depth);
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
     * @param callable(VerificationFinding, string): void $record
     *
     * @throws Exception
     */
    private function verifyChecksums(string $classId, callable $record): void
    {
        $after = null;

        while (($rows = $this->readDocumentRows($classId, $after)) !== []) {
            foreach ($rows as $row) {
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
                        $record(
                            VerificationFinding::Unproven,
                            $where . ': written before checksums were stamped'
                        );
                    } else {
                        $record(
                            VerificationFinding::Failure,
                            $where . ': a drained document carries no checksum'
                        );
                    }

                    continue;
                }

                if (!hash_equals((string) $row['checksum'], sha1((string) $row['values']))) {
                    $record(
                        VerificationFinding::Failure,
                        $where . ': the stored checksum does not match its values'
                    );
                }
            }

            $last = $rows[array_key_last($rows)];
            $after = [(int) $last['id'], (string) $last['fieldname'], (int) $last['groupId']];
        }
    }

    /**
     * One batch of document rows, from the primary key the previous batch ended on.
     *
     * The `values` column is a LONGTEXT holding everything one group of one object stores, so a
     * query over the whole table would put the entire stored data of the class in memory at once -
     * on exactly the installations this migration is for. The table is walked along its primary
     * key instead, {@see self::VERIFY_BATCH_SIZE} rows at a time, which bounds both the verify and
     * the cleanup that re-runs it to one batch. The row-value comparison is what lets the three
     * leading primary key columns be resumed from with the index.
     *
     * @param array{0: int, 1: string, 2: int}|null $after the (id, fieldname, groupId) to resume after
     *
     * @return array<array<string, mixed>>
     *
     * @throws Exception
     */
    private function readDocumentRows(string $classId, ?array $after = null): array
    {
        $sql = 'SELECT `id`, `fieldname`, `groupId`, `values`, `checksum`, `migratedAt` FROM '
            . $this->db->quoteIdentifier($this->documentEngine->getDocumentTableName($classId));
        $parameters = [];
        $types = [];

        if ($after !== null) {
            [$condition, $parameters, $types] = $this->buildKeysetSeek(
                ['id', 'fieldname', 'groupId'],
                $after
            );
            $sql .= ' WHERE ' . $condition;
        }

        $sql .= ' ORDER BY `id`, `fieldname`, `groupId` LIMIT ' . self::VERIFY_BATCH_SIZE;

        try {
            return $this->db->fetchAllAssociative($sql, $parameters, $types);
        } catch (TableNotFoundException) {
            return [];
        }
    }

    /**
     * Where the next batch of a walk along the leading columns of an index starts.
     *
     * The row-constructor form of this - `(a, b) > (?, ?)` - reads better and is not a range
     * condition to the optimizer: it starts at the beginning of the index and filters, so a walk of
     * n batches scans the index n times and the cost of reading a table in batches grows with the
     * square of its size. Expanded by hand the same condition is a seek. Measured on MariaDB 10.11
     * over 50,000 rows, a batch of 500 taken from the middle: 998 index reads expanded against
     * 50,998 as a row constructor, for the same rows.
     *
     * @param string[]          $columns the index columns, in index order
     * @param array<int, mixed> $after   the row to resume after, one value per column
     *
     * @return array{0: string, 1: array<mixed>, 2: array<ParameterType>}
     */
    private function buildKeysetSeek(array $columns, array $after): array
    {
        $parameters = [];
        $types = [];
        $last = count($columns) - 1;

        // every column but the last is compared twice, once for `>` and once for the `=` that
        // carries the comparison on to the next one
        foreach ($after as $index => $value) {
            $repeats = $index < $last ? 2 : 1;

            for ($repeat = 0; $repeat < $repeats; $repeat++) {
                $parameters[] = $value;
                $types[] = is_int($value) ? ParameterType::INTEGER : ParameterType::STRING;
            }
        }

        return [$this->buildKeysetCondition($columns), $parameters, $types];
    }

    /**
     * @param string[] $columns
     */
    private function buildKeysetCondition(array $columns): string
    {
        $column = $this->db->quoteIdentifier((string) array_shift($columns));

        if ($columns === []) {
            return $column . ' > ?';
        }

        return '(' . $column . ' > ? OR (' . $column . ' = ? AND '
            . $this->buildKeysetCondition($columns) . '))';
    }

    /**
     * One batch of pending pairs, from the pair the previous batch ended on.
     *
     * @param string[]                      $fieldNames
     * @param array{0: int, 1: string}|null $after the (objectId, fieldname) to resume after
     *
     * @return array<array{0: int, 1: string}>
     *
     * @throws Exception
     */
    private function readPairs(string $classId, array $fieldNames, ?array $after): array
    {
        $union = $this->buildPairUnion($classId, $fieldNames, $after, self::BACKLOG_BATCH_SIZE);
        if ($union === null) {
            return [];
        }

        [$sql, $parameters, $types] = $union;

        $rows = $this->db->fetchAllAssociative(
            $sql . self::ORDER_BY_PAIR . ' LIMIT ' . self::BACKLOG_BATCH_SIZE,
            $parameters,
            $types
        );

        return array_map(
            static fn (array $row): array => [(int) $row['id'], (string) $row['fieldname']],
            $rows
        );
    }

    /**
     * The pending pairs of a class as one `UNION` over its two value tables, which is what makes a
     * pair both of them hold one pair - the count and the stream are then the same set.
     *
     * The keyset seek of {@see self::buildKeysetSeek()} goes into each branch rather than around the
     * union, so that a batch starts at a position in each table's primary key rather than at the
     * beginning of it. `$limit` goes into each branch as well, and has to: a `UNION` has to produce
     * both branches in full before a `LIMIT` on the union can be applied, so the limit alone around
     * it would read what is left of both tables for every batch. Bounding each branch to the same
     * `$limit` is what makes a batch cost a batch - and it reads the same pairs, because a pair
     * among the first `$limit` of the union is among the first `$limit` of every branch that holds
     * it. A branch carrying its own `LIMIT` has to be parenthesised, which the caller's trailing
     * `ORDER BY` and `LIMIT` then apply past, to the union as a whole.
     *
     * A table that is not there contributes no branch: a store born on the document engine never
     * had one, and the cleanup of a converted class drops both. With neither there the class has no
     * backlog at all, which is what the null answers.
     *
     * @param string[]                      $fieldNames
     * @param array{0: int, 1: string}|null $after
     * @param int|null                      $limit the pairs one branch may contribute, or null for
     *                                             all of them
     *
     * @return array{0: string, 1: array<mixed>, 2: array<ArrayParameterType|ParameterType>}|null
     *
     * @throws Exception
     */
    private function buildPairUnion(string $classId, array $fieldNames, ?array $after, ?int $limit): ?array
    {
        $branches = [];
        $parameters = [];
        $types = [];

        foreach ($this->getEavTableNames($classId) as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }

            // DISTINCT, and it carries the batch: both tables hold many rows per pair - a value row
            // per key and language, a group row per group - and a LIMIT that counted those instead
            // of pairs would hand back fewer pairs than it was asked for while the cursor moved on
            // past the ones it did not return
            $branch = 'SELECT DISTINCT `id`, `fieldname` FROM ' . $this->db->quoteIdentifier($table)
                . ' WHERE `fieldname` IN (?)';
            $parameters[] = $fieldNames;
            $types[] = ArrayParameterType::STRING;

            if ($after !== null) {
                [$condition, $seekParameters, $seekTypes] = $this->buildKeysetSeek(
                    ['id', 'fieldname'],
                    $after
                );

                $branch .= ' AND ' . $condition;
                $parameters = [...$parameters, ...$seekParameters];
                $types = [...$types, ...$seekTypes];
            }

            if ($limit !== null) {
                $branch = '(' . $branch . self::ORDER_BY_PAIR . ' LIMIT ' . $limit . ')';
            }

            $branches[] = $branch;
        }

        if ($branches === []) {
            return null;
        }

        return [implode(' UNION ', $branches), $parameters, $types];
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
     * A store as a refusal has to name it: by id as well as by name, which is what an operator
     * passes to the next command.
     */
    private function describeStore(?int $storeId): string
    {
        if ($storeId === null) {
            return self::DEFAULT_STORE_NAME;
        }

        return sprintf('store %d ("%s")', $storeId, $this->nameStore($storeId));
    }

    /**
     * @return ClassDefinition[]
     */
    private function loadClassDefinitions(): array
    {
        return (new ClassDefinitionListing())->load();
    }
}
