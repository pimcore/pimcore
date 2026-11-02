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

namespace Pimcore\Model\DataObject\Classificationstore\Engine;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\ParameterType;
use Exception;
use Pimcore\Element\MarshallerService;
use Pimcore\Marshaller\MarshallerInterface;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Helper\Dao as ClassDefinitionDaoHelper;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Normalizer\NormalizerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Stores classification store values as one JSON document per object, field and group, in one table
 * per class.
 *
 * The presence of a row is what makes a group active, so there is no second table: a group that was
 * activated without a value stores the empty document `{}`, and deactivating it deletes the row.
 *
 * The document is canonical - its keys and its languages are written in a fixed order - so the save
 * can compare the serialized document against the stored one and write only the groups that differ.
 * A changed value does rewrite its own group's document, which is what one document per group means;
 * the documents of the other groups are not touched.
 *
 * Values are stored as what {@see NormalizerInterface::normalize()} returns, with one named
 * exception: the `encryptedField` marshaller does not pack a value into two columns, it encrypts it,
 * so skipping it would store secrets in cleartext. {@see self::encodesThroughItsMarshaller()}. That
 * encryption is randomized, so a value that encodes through it keeps the string the document already
 * holds as long as it is still the same value - otherwise every save would rewrite every group that
 * carries a secret. {@see self::encodesTheSameValue()}.
 *
 * The values of a store that was flipped to this engine are read through to the EAV tables until the
 * drain reaches them: a (object, fieldname) pair holding no document at all is read from the EAV
 * engine, a pair holding at least one document from the document alone. Nothing is merged, and a
 * save of such a pair writes its documents and takes its EAV rows with it.
 * {@see \Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DocumentEngineMigrationService}
 * is what drives that migration.
 *
 * @internal
 */
final class DocumentEngine extends AbstractDao implements EngineInterface
{
    use ClassDefinitionDaoHelper;

    private const DOCUMENT_TABLE_PREFIX = 'object_classificationstore_doc_';

    /**
     * The envelope version of a stored document, so that a later reader can tell which shape it is
     * looking at. It is written beside the keys rather than in a column of its own, as a document
     * that travels out of its row - into a version, a cache entry, an export - carries it that way.
     */
    private const DOCUMENT_VERSION = 1;

    private const VERSION_MEMBER = 'v';

    private const KEYS_MEMBER = 'k';

    /**
     * The language a value belongs to. Absent for the default language, which is the one almost
     * every value of a non-localized store carries.
     */
    private const LANGUAGE_QUALIFIER = 'l';

    private const VALUE_QUALIFIER = 'd';

    /**
     * A group that is active but holds no value at all. It carries no version marker: there is
     * nothing in it whose reading a version could govern, and {@see self::deserializeDocument()}
     * reads it without a case of its own.
     */
    private const EMPTY_DOCUMENT = '{}';

    private const DEFAULT_LANGUAGE = 'default';

    /**
     * The one field type whose classificationstore marshaller is applied before the value reaches
     * the document, because that marshaller encrypts rather than packs.
     */
    private const ENCRYPTED_FIELD_TYPE = 'encryptedField';

    private const MARSHALLER_FORMAT = 'classificationstore';

    private const DOCUMENT_COLUMNS = ['id', 'fieldname', 'groupId', 'collectionId', 'values', 'checksum'];

    /**
     * What the drain writes on top of what a save writes. `migratedAt` is stamped by the drain
     * alone, so a document carrying none was written by a user save.
     */
    private const MIGRATED_DOCUMENT_COLUMNS = [
        'id', 'fieldname', 'groupId', 'collectionId', 'values', 'checksum', 'migratedAt',
    ];

    /**
     * The columns a write updates when the row of that group is already there: everything the save
     * writes that is not part of the primary key. `migratedAt` is not among them, so a user save of
     * a drained document leaves the stamp of the run that converted it.
     */
    private const ON_DUPLICATE_UPDATE = ' ON DUPLICATE KEY UPDATE `collectionId` = VALUES(`collectionId`),'
        . ' `values` = VALUES(`values`), `checksum` = VALUES(`checksum`)';

    /**
     * Rows per multi-row statement, so that an object on many groups does not build a statement of
     * unbounded length. What the rows weigh is bounded separately, by
     * {@see self::getWriteBatchByteBudget()}.
     */
    private const WRITE_BATCH_SIZE = 200;

    /**
     * The payload a single multi-row statement may carry when the server does not answer what it
     * accepts.
     */
    private const WRITE_BATCH_FALLBACK_BYTES = 1048576;

    /**
     * @see self::getWriteBatchByteBudget()
     */
    private static ?int $writeBatchByteBudget = null;

    protected array $tableDefinitions = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MarshallerService $marshallerService,
        private readonly KeyGroupRelationCache $relationCache,
        private readonly EavEngine $eavEngine,
        Connection $db
    ) {
        $this->db = $db;
    }

    /**
     * @throws Exception
     */
    public function save(Classificationstore $classificationStore): void
    {
        if (!DataObject::isDirtyDetectionDisabled() && !$classificationStore->hasDirtyFields()) {
            return;
        }

        $this->persistDocuments($classificationStore, $this->collectGroups($classificationStore));
    }

    /**
     * The collection and the items of every active group, which is what a document is written from.
     *
     * @return array<int, array<string, mixed>> groupId => the collection and items of a group
     */
    private function collectGroups(Classificationstore $classificationStore): array
    {
        $items = $classificationStore->getItems();
        $activeGroups = $classificationStore->getActiveGroups();
        $collectionMapping = $this->getCollectionMappingToStore($classificationStore);

        // the serialization below resolves a key config per key, which on a cold cache - a save
        // that did not load the store first - would be a statement each
        DefinitionCache::load($this->getItemKeyIds($items, $activeGroups));

        $groups = [];

        foreach (array_keys($activeGroups) as $groupId) {
            $groups[$groupId] = [
                'collectionId' => $collectionMapping[$groupId] ?? null,
                'keys' => $items[$groupId] ?? [],
            ];
        }

        return $groups;
    }

    /**
     * Writes the documents of one (object, fieldname) pair as the drain writes them, and only when
     * the pair holds no document at all: a user save that converted the pair while the drain was
     * running must not be overwritten with the EAV rows it has already replaced.
     *
     * The rows carry the run's timestamp in `migratedAt`, which no other writer sets. Nothing is
     * compared and nothing is deleted here - the drain compares the written documents against the
     * EAV side and removes the EAV rows itself, inside the transaction it has to own for that.
     *
     * @return bool whether the documents were written, false when the pair already held one
     *
     * @throws Exception
     */
    public function writeMigratedDocuments(Classificationstore $classificationStore, int $migratedAt): bool
    {
        $object = $classificationStore->getObject();
        $objectId = $object->getId();
        $fieldname = $classificationStore->getFieldname();
        $table = $this->getDocumentTableName($classificationStore->getClass()->getId());

        if ($this->readRowsForUpdate($table, $objectId, $fieldname) !== []) {
            return false;
        }

        $rows = [];

        foreach ($this->collectGroups($classificationStore) as $groupId => $group) {
            $values = $this->serializeDocument($object, $group['keys'], null);

            $rows[] = [
                'id' => $objectId,
                'fieldname' => $fieldname,
                'groupId' => $groupId,
                'collectionId' => $group['collectionId'],
                'values' => $values,
                'checksum' => sha1($values),
                'migratedAt' => $migratedAt,
            ];
        }

        $this->insertDocumentRows($table, self::MIGRATED_DOCUMENT_COLUMNS, $rows);

        return true;
    }

    /**
     * Whether the given pair holds at least one document, which is what makes it converted.
     *
     * @throws Exception
     */
    public function hasDocuments(string $classId, int $objectId, string $fieldname): bool
    {
        return $this->db->fetchOne(
            'SELECT 1 FROM `' . $this->getDocumentTableName($classId) . '`'
            . ' WHERE `id` = ? AND `fieldname` = ? LIMIT 1',
            [$objectId, $fieldname]
        ) !== false;
    }

    public function delete(Classificationstore $classificationStore): void
    {
        $this->db->delete(
            $this->getDocumentTableName($classificationStore->getClass()->getId()),
            ['id' => $classificationStore->getObject()->getId()]
        );
    }

    /**
     * @throws Exception
     */
    public function load(Classificationstore $classificationStore): void
    {
        $object = $classificationStore->getObject();
        $rows = $this->readDocumentRows($classificationStore);

        // a pair that holds no document at all has not been drained yet, and its values are still
        // the EAV rows. A pair that holds at least one is read from the documents alone, so the
        // two sources are never merged and nothing is derived from both
        if ($rows === []) {
            $this->readThroughEav($classificationStore);

            return;
        }

        $activeGroups = [];
        $groupCollectionMapping = [];
        $documents = [];

        foreach ($rows as $row) {
            $groupId = (int) $row['groupId'];
            $activeGroups[$groupId] = true;
            $documents[$groupId] = $this->deserializeDocument((string) $row['values']);

            if ($row['collectionId'] !== null) {
                $groupCollectionMapping[$groupId] = (int) $row['collectionId'];
            }
        }

        $this->relationCache->load(array_keys($activeGroups));

        // the relations above bring the key configs of the keys that are still in their group with
        // them; the ones of values whose key was removed from the group are read here, in one go,
        // so that the fallback below does not resolve them a statement at a time
        DefinitionCache::load($this->getStoredKeyIds($documents));

        foreach ($documents as $groupId => $keys) {
            $this->applyDocument($classificationStore, $object, $groupId, $keys);
        }

        $classificationStore->setActiveGroups($activeGroups);
        $classificationStore->setGroupCollectionMappings($groupCollectionMapping);
        $classificationStore->resetDirtyMap();
    }

    /**
     * The stored documents of one (object, fieldname) pair.
     *
     * An absent table reads as no document rather than as an error: a store whose `engine` column
     * was edited by hand is on this engine before any class carrying it has been saved, and a read
     * must not raise table-not-found over that.
     *
     * @return array<array<string, mixed>>
     *
     * @throws Exception
     */
    private function readDocumentRows(Classificationstore $classificationStore): array
    {
        $table = $this->getDocumentTableName($classificationStore->getClass()->getId());

        try {
            return $this->db->fetchAllAssociative(
                'SELECT `groupId`, `collectionId`, `values` FROM `' . $table . '`'
                . ' WHERE `id` = ? AND `fieldname` = ?',
                [$classificationStore->getObject()->getId() ?? 0, $classificationStore->getFieldname()]
            );
        } catch (TableNotFoundException) {
            return [];
        }
    }

    /**
     * Reads a pair the drain has not converted yet from the EAV tables it still lives in.
     *
     * The values are derived, not stored: nothing is written here and no column records that the
     * answer came from the other engine. A class whose EAV tables were never created - a store born
     * on this engine - or whose tables the cleanup has dropped reads as an empty store.
     *
     * @throws Exception
     */
    private function readThroughEav(Classificationstore $classificationStore): void
    {
        try {
            $this->eavEngine->load($classificationStore);
        } catch (TableNotFoundException) {
            $classificationStore->setActiveGroups([]);
            $classificationStore->setGroupCollectionMappings([]);
            $classificationStore->resetDirtyMap();
        }
    }

    public function createUpdateTables(ClassDefinition $class, ?int $storeId): void
    {
        $table = $this->getDocumentTableName($class->getId());

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $table . '` (
            `id` INT(11) UNSIGNED NOT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            `groupId` INT(11) UNSIGNED NOT NULL,
            `collectionId` BIGINT(20) NULL,
            `values` LONGTEXT NOT NULL,
            `checksum` CHAR(40) NULL,
            `migratedAt` INT UNSIGNED NULL,
            PRIMARY KEY (`id`, `fieldname`, `groupId`),
            CONSTRAINT `'.self::getForeignKeyName($table, 'id').'` FOREIGN KEY (`id`) REFERENCES `objects` (`id`) ON DELETE CASCADE,
            CONSTRAINT `'.self::getForeignKeyName($table, 'groupId').'` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;');

        $this->tableDefinitions = [];

        $this->handleEncryption($class, [$table]);
    }

    public function dropTables(ClassDefinition $class): void
    {
        $this->db->executeQuery('DROP TABLE IF EXISTS ' . $this->getDocumentTableName($class->getId()));
    }

    public function cleanup(): void
    {
        foreach ($this->getTableNames() as $tableName) {
            $this->cleanupTable($tableName, substr($tableName, strlen(self::DOCUMENT_TABLE_PREFIX)));
        }
    }

    public function deleteStoreValues(int $storeId): void
    {
        foreach ($this->getTableNames() as $tableName) {
            $this->db->executeStatement(
                'DELETE FROM ' . $this->db->quoteIdentifier($tableName)
                . ' WHERE groupId IN (SELECT id FROM classificationstore_groups WHERE storeId = :storeId)',
                ['storeId' => $storeId]
            );
        }
    }

    public function supports(EngineFeature $feature): bool
    {
        return false;
    }

    /**
     * The collection mappings this object owns, which is what its rows carry.
     *
     * A mapping that is identical to the one an inheritable parent holds is left out, as the parent
     * is what answers it: {@see Classificationstore::getGroupCollectionMappings()} merges the
     * parents' mappings into the object's own when inheritance is on. The object is checked for a
     * parent first, so that the mappings of a top-level object are not mistaken for inherited ones.
     *
     * @return array<int, int>
     */
    private function getCollectionMappingToStore(Classificationstore $classificationStore): array
    {
        $collectionMapping = $classificationStore->getGroupCollectionMappings();

        if (!$classificationStore->getClass()->getAllowInherit()) {
            return $collectionMapping;
        }

        $parent = DataObjectService::hasInheritableParentObject($classificationStore->getObject());
        if (!$parent) {
            return $collectionMapping;
        }

        $parentStore = $parent->{'get' . ucfirst($classificationStore->getFieldname())}();
        if (!$parentStore instanceof Classificationstore) {
            return $collectionMapping;
        }

        return array_diff_assoc(
            $collectionMapping,
            DataObjectService::useInheritedValues(true, $parentStore->getGroupCollectionMappings(...))
        );
    }

    /**
     * The document of one group, in the one shape its items can produce.
     *
     * The keys are written in the order of their id and the values of a key in the order of their
     * language, so that the same items always serialize to the same string - which is what lets the
     * save compare documents instead of rewriting them.
     *
     * Two spellings of one language that differ only in case are one and the same value, as they are
     * under the primary key of the EAV value table: the last of them is kept, with its own spelling.
     *
     * A value that encodes through a randomized marshaller is compared against what the stored
     * document holds for that key and language, so that it serializes to the string it already had
     * when it is still the same value. The stored document is read back only for a key of that kind,
     * as an ordinary value encodes to the same string whatever is stored.
     *
     * @param array<int, array<string, mixed>> $keys structure: [keyId][language] = value
     *
     * @throws Exception
     */
    private function serializeDocument(?Concrete $object, array $keys, ?string $storedDocument): string
    {
        $document = [];
        $storedKeys = null;

        foreach ($keys as $keyId => $keyData) {
            $fd = Service::getFieldDefinitionFromKeyConfig(DefinitionCache::get((int) $keyId));
            $entries = [];
            $storedEntries = [];

            if ($fd instanceof Data && $this->encodesThroughItsMarshaller($fd)) {
                $storedKeys ??= $storedDocument === null ? [] : $this->deserializeDocument($storedDocument);
                $storedEntries = $storedKeys[(int) $keyId] ?? [];
            }

            foreach ($keyData as $language => $value) {
                $languageAlias = $this->getLanguageAlias((string) $language);

                $entries[$languageAlias] = $this->buildEntry(
                    (string) $language,
                    $this->encodeValue($fd, $value, $object, $this->getStoredValue($storedEntries, $languageAlias))
                );
            }

            // a key that carries no value at all is not a member of the document, so that it
            // serializes to the same string as items that never mentioned the key
            if ($entries === []) {
                continue;
            }

            ksort($entries);
            $document[(int) $keyId] = array_values($entries);
        }

        if ($document === []) {
            return self::EMPTY_DOCUMENT;
        }

        ksort($document);

        return json_encode(
            [self::VERSION_MEMBER => self::DOCUMENT_VERSION, self::KEYS_MEMBER => $document],
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array<int, array<array<string, mixed>>> structure: [keyId][] = entry
     *
     * @throws Exception
     */
    private function deserializeDocument(string $values): array
    {
        $document = json_decode($values, true, 512, JSON_THROW_ON_ERROR);

        return is_array($document) ? ($document[self::KEYS_MEMBER] ?? []) : [];
    }

    /**
     * @param array<int, array<array<string, mixed>>> $keys
     *
     * @throws Exception
     */
    private function applyDocument(
        Classificationstore $classificationStore,
        ?Concrete $object,
        int $groupId,
        array $keys
    ): void {
        foreach ($keys as $keyId => $entries) {
            $fd = $this->resolveFieldDefinition($groupId, (int) $keyId);
            if (!$fd) {
                continue;
            }

            foreach ($entries as $entry) {
                $classificationStore->setLocalizedKeyValue(
                    $groupId,
                    (int) $keyId,
                    $this->decodeValue($fd, $entry[self::VALUE_QUALIFIER] ?? null, $object),
                    $entry[self::LANGUAGE_QUALIFIER] ?? self::DEFAULT_LANGUAGE
                );
            }
        }
    }

    /**
     * The field definition of a stored value: the one of its key's assignment to the group, or - for
     * a value whose key was removed from the group - the one of the key config itself.
     */
    private function resolveFieldDefinition(int $groupId, int $keyId): ?Data
    {
        $fd = $this->relationCache->getFieldDefinition($groupId, $keyId);
        if ($fd) {
            return $fd;
        }

        $keyConfig = DefinitionCache::get($keyId);
        if (!$keyConfig) {
            $this->logger->error('Could not resolve key with ID: ' . $keyId);

            return null;
        }

        return Service::getFieldDefinitionFromKeyConfig($keyConfig);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEntry(string $language, mixed $value): array
    {
        $entry = [];

        if ($language !== self::DEFAULT_LANGUAGE) {
            $entry[self::LANGUAGE_QUALIFIER] = $language;
        }

        $entry[self::VALUE_QUALIFIER] = $value;

        return $entry;
    }

    /**
     * The language as the document addresses it: of the characters a language can hold, what the
     * EAV primary key equates under utf8mb4_unicode_520_ci is the case of its letters and, as a PAD
     * SPACE collation, the spaces it ends on.
     */
    private function getLanguageAlias(string $language): string
    {
        return rtrim(mb_strtolower($language), ' ');
    }

    /**
     * @param mixed $storedValue what the stored document holds for this key and language, against
     *                           which a randomized encoding is compared
     */
    private function encodeValue(?Data $fd, mixed $value, ?Concrete $object, mixed $storedValue): mixed
    {
        if (!$fd instanceof NormalizerInterface) {
            return null;
        }

        $normalizedData = $fd->normalize($value, ['object' => $object, 'fieldDefinition' => $fd]);

        if (!$this->encodesThroughItsMarshaller($fd)) {
            return $normalizedData;
        }

        $marshaller = $this->marshallerService
            ->buildFieldefinitionMarshaller(self::MARSHALLER_FORMAT, $fd->getFieldtype());
        $parameters = $this->getMarshallerParameters($fd, $object);
        $encodedValue = $marshaller->marshal($normalizedData, $parameters);

        if ($storedValue !== null
            && $encodedValue !== null
            && $this->encodesTheSameValue($marshaller, $parameters, $storedValue, $encodedValue)
        ) {
            return $storedValue;
        }

        return $encodedValue;
    }

    /**
     * Whether a stored value and a freshly encoded one hold the same value.
     *
     * The one marshaller a value reaches the document through encrypts, and
     * {@see \Defuse\Crypto\Crypto::encrypt()} draws a random salt and IV per call: the same secret
     * encodes to a different string every time, so the two strings cannot be compared as they
     * stand. They are read back through the marshaller that wrote them and compared as the values
     * they carry instead - both sides through the same path, so what that path does not preserve
     * is not mistaken for a change. When they are the same value the stored string is kept, which
     * is what lets an unchanged save write nothing and a save of one group leave the documents of
     * the groups that hold a secret alone. A value that did change is written as it was just
     * encoded, with the fresh salt of that encryption.
     *
     * Only an Exception is an answer about the stored string - that is what an unreadable one
     * arrives as, {@see \Pimcore\DataObject\ClassificationstoreDataMarshaller\EncryptedField::decrypt()}.
     * A PHP Error is a fault in this code rather than evidence about a value, so it propagates and
     * takes the save down with it instead of being logged and answered with "not the same value".
     *
     * @param array<string, mixed> $parameters
     */
    private function encodesTheSameValue(
        MarshallerInterface $marshaller,
        array $parameters,
        mixed $storedValue,
        mixed $encodedValue
    ): bool {
        try {
            return $marshaller->unmarshal($storedValue, $parameters)
                === $marshaller->unmarshal($encodedValue, $parameters);
        } catch (Exception $exception) {
            // a stored value that cannot be read back - one written under a key that has since been
            // rotated, say - is no evidence about the value it holds, so the fresh encoding wins
            $this->logger->info(
                'Could not read back a stored classification store value for comparison: '
                . $exception->getMessage()
            );

            return false;
        }
    }

    /**
     * The value the stored entries of one key hold in one language, or null when they do not carry
     * that language.
     *
     * @param array<array<string, mixed>> $entries
     */
    private function getStoredValue(array $entries, string $languageAlias): mixed
    {
        foreach ($entries as $entry) {
            $language = (string) ($entry[self::LANGUAGE_QUALIFIER] ?? self::DEFAULT_LANGUAGE);

            if ($this->getLanguageAlias($language) === $languageAlias) {
                return $entry[self::VALUE_QUALIFIER] ?? null;
            }
        }

        return null;
    }

    private function decodeValue(Data $fd, mixed $value, ?Concrete $object): mixed
    {
        if (!$fd instanceof NormalizerInterface) {
            return null;
        }

        if ($this->encodesThroughItsMarshaller($fd)) {
            $value = $this->marshallerService
                ->buildFieldefinitionMarshaller(self::MARSHALLER_FORMAT, $fd->getFieldtype())
                ->unmarshal($value, $this->getMarshallerParameters($fd, $object));
        }

        return $fd->denormalize($value, ['object' => $object, 'fieldDefinition' => $fd]);
    }

    /**
     * Whether the value of this field type reaches the document through its classificationstore
     * marshaller rather than as plain normalized data.
     *
     * The per-type marshallers pack a value into the two value columns of the EAV table, which a
     * document does not have and does not need. `encryptedField` is the exception that matters: its
     * marshaller encrypts with Defuse Crypto, so storing the normalized value instead would write
     * the secret into the document in cleartext.
     */
    private function encodesThroughItsMarshaller(Data $fd): bool
    {
        return $fd->getFieldtype() === self::ENCRYPTED_FIELD_TYPE
            && $this->marshallerService->supportsFielddefinition(self::MARSHALLER_FORMAT, self::ENCRYPTED_FIELD_TYPE);
    }

    /**
     * @return array<string, mixed>
     */
    private function getMarshallerParameters(Data $fd, ?Concrete $object): array
    {
        // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
        return ['object' => $object, 'fieldDefinition' => $fd, 'format' => self::MARSHALLER_FORMAT];
    }

    /**
     * Writes only the documents that differ, under a transaction that covers the whole operation.
     *
     * What is written is decided from what the table holds, so the read, the serialization that is
     * compared against it and the writes that follow have to see one state and hold it. A store
     * saved as part of its object runs inside the
     * object's save transaction, which may have read from the database before this point: under
     * REPEATABLE READ its snapshot can predate a save another connection has committed since, and
     * the object's row lock - taken by {@see \Pimcore\Model\DataObject\AbstractObject::save()} -
     * serializes the two saves but does not refresh that snapshot. A document that the snapshot
     * reports as already holding the value would then be skipped, leaving the other connection's
     * value stored. {@see self::readRowsForUpdate()} therefore reads the table as of now, and holds
     * it. A store saved on its own gets a transaction of its own here, so that its locks cover its
     * writes as well.
     *
     * @param array<int, array<string, mixed>> $groups groupId => the collection and items of a group
     *
     * @throws Exception
     */
    private function persistDocuments(Classificationstore $classificationStore, array $groups): void
    {
        $ownTransaction = !$this->db->isTransactionActive();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $this->writeDocumentDiff($classificationStore, $groups);
        } catch (Throwable $exception) {
            if ($ownTransaction) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        if ($ownTransaction) {
            $this->db->commit();
        }
    }

    /**
     * The stored documents of (object, fieldname) are read back, and the document of each active
     * group is serialized against the stored one and compared with it, as strings. A group whose
     * document and collection are unchanged is not written, so an unchanged save writes nothing and
     * a save touching one group leaves the documents of the other groups byte for byte as they were.
     *
     * @param array<int, array<string, mixed>> $groups groupId => the collection and items of a group
     *
     * @throws Exception
     */
    private function writeDocumentDiff(Classificationstore $classificationStore, array $groups): void
    {
        $object = $classificationStore->getObject();
        $objectId = $object->getId();
        $fieldname = $classificationStore->getFieldname();
        $table = $this->getDocumentTableName($classificationStore->getClass()->getId());

        $storedRows = $this->readRowsForUpdate($table, $objectId, $fieldname);
        $rowsToWrite = [];

        foreach ($groups as $groupId => $group) {
            $storedRow = $storedRows[$groupId] ?? null;

            $document = [
                'collectionId' => $group['collectionId'],
                'values' => $this->serializeDocument(
                    $object,
                    $group['keys'],
                    $storedRow === null ? null : (string) $storedRow['values']
                ),
            ];

            if ($storedRow !== null && !$this->documentDiffers($storedRow, $document)) {
                continue;
            }

            $rowsToWrite[] = [
                'id' => $objectId,
                'fieldname' => $fieldname,
                'groupId' => $groupId,
                'collectionId' => $document['collectionId'],
                'values' => $document['values'],
                'checksum' => sha1($document['values']),
            ];
        }

        $obsoleteGroupIds = array_values(array_diff(array_keys($storedRows), array_keys($groups)));

        $this->insertDocumentRows($table, self::DOCUMENT_COLUMNS, $rowsToWrite, self::ON_DUPLICATE_UPDATE);
        $this->deleteDocumentRows($table, $obsoleteGroupIds, $objectId, $fieldname);

        if ($storedRows === [] || $rowsToWrite !== [] || $obsoleteGroupIds !== []) {
            $this->takeThePairOffTheEavTables($classificationStore, $objectId, $fieldname);
        }
    }

    /**
     * The documents of this pair are now what it is read from, so its EAV rows go with them - in
     * this transaction and without being compared first. Whether the drain has reached the pair or
     * not makes no difference: this save has just converted it.
     *
     * A save that found the pair's documents already in the state it wants is not reached from
     * here, so an unchanged save still writes nothing at all: a pair that holds documents has no
     * EAV rows left to remove. A pair that holds none is reached whatever it writes - a save that
     * deactivated every group of such a pair writes no document and still has to take its rows,
     * which the read-through would otherwise serve again on the next load.
     *
     * @throws Exception
     */
    private function takeThePairOffTheEavTables(
        Classificationstore $classificationStore,
        ?int $objectId,
        string $fieldname
    ): void {
        $this->eavEngine->deletePairRows($classificationStore->getClass()->getId(), $objectId, $fieldname);
    }

    /**
     * The stored documents of one (object, fieldname), read as of now and locked until the end of
     * the transaction: a locking read is not answered from the transaction's snapshot, so the
     * comparison decides against what the table holds, and no other connection can change these
     * rows while the writes that follow are being made. The table is read through the leading
     * columns of its primary key, so the rows this object owns are all that is locked.
     *
     * @return array<int, array<string, mixed>> groupId => stored row
     *
     * @throws Exception
     */
    private function readRowsForUpdate(string $table, ?int $objectId, string $fieldname): array
    {
        $rows = [];

        $storedRows = $this->db->fetchAllAssociative(
            'SELECT `groupId`, `collectionId`, `values` FROM `' . $table . '`'
            . ' WHERE `id` = ? AND `fieldname` = ? FOR UPDATE',
            [$objectId, $fieldname]
        );

        foreach ($storedRows as $storedRow) {
            $rows[(int) $storedRow['groupId']] = $storedRow;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $storedRow
     * @param array<string, mixed> $document
     */
    private function documentDiffers(array $storedRow, array $document): bool
    {
        return (string) $storedRow['values'] !== $document['values']
            || $this->normalizeColumnValue($storedRow['collectionId'])
                !== $this->normalizeColumnValue($document['collectionId']);
    }

    /**
     * The stored value of a column as the driver reads it back, so that a collection id written as
     * an int is not seen as changed on the next save.
     */
    private function normalizeColumnValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param string[] $columns
     * @param array<array<string, mixed>> $rows
     *
     * @throws Exception
     */
    private function insertDocumentRows(string $table, array $columns, array $rows, string $onDuplicate = ''): void
    {
        if (!$rows) {
            return;
        }

        $quotedColumns = array_map(static fn (string $column) => '`' . $column . '`', $columns);
        $rowPlaceholder = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $statement = 'INSERT INTO `' . $table . '` (' . implode(',', $quotedColumns) . ') VALUES ';
        $byteBudget = $this->getWriteBatchByteBudget();

        $parameters = [];
        $rowCount = 0;
        $size = 0;

        foreach ($rows as $row) {
            $rowParameters = [];
            $rowSize = 0;
            foreach ($columns as $column) {
                $rowParameters[] = $row[$column];
                $rowSize += $this->getParameterSize($row[$column]);
            }

            // a row of its own is emitted whatever it weighs: a single document can exceed any
            // budget, and no batching makes that statement smaller
            if ($rowCount > 0 && ($rowCount >= self::WRITE_BATCH_SIZE || $size + $rowSize > $byteBudget)) {
                $this->executeInsert($statement, $rowPlaceholder, $rowCount, $parameters, $onDuplicate);

                $parameters = [];
                $rowCount = 0;
                $size = 0;
            }

            foreach ($rowParameters as $parameter) {
                $parameters[] = $parameter;
            }
            $rowCount++;
            $size += $rowSize;
        }

        $this->executeInsert($statement, $rowPlaceholder, $rowCount, $parameters, $onDuplicate);
    }

    /**
     * What a parameter is worth against the packet budget: the bytes of its value, plus the
     * placeholder and the separators it travels with.
     */
    private function getParameterSize(mixed $value): int
    {
        return (is_string($value) ? strlen($value) : 8) + 4;
    }

    /**
     * @param array<mixed> $parameters
     *
     * @throws Exception
     */
    private function executeInsert(
        string $statement,
        string $rowPlaceholder,
        int $rowCount,
        array $parameters,
        string $onDuplicate
    ): void {
        $this->db->executeStatement(
            $statement . implode(',', array_fill(0, $rowCount, $rowPlaceholder)) . $onDuplicate,
            $parameters
        );
    }

    /**
     * What one multi-row statement may carry: half of what the server accepts, so that a worst case
     * in which quoting doubles every byte of a document still fits into a single packet. A row count
     * alone does not bound a statement - the document column is LONGTEXT, and a group without a key
     * limit can fill it.
     *
     * The limit is read once per process: it is a server setting, and asking for it on every save
     * would cost a statement of the kind this batching saves.
     *
     * @throws Exception
     */
    private function getWriteBatchByteBudget(): int
    {
        if (self::$writeBatchByteBudget === null) {
            $maxAllowedPacket = (int) $this->db->fetchOne('SELECT @@max_allowed_packet');

            self::$writeBatchByteBudget = $maxAllowedPacket > 0
                ? intdiv($maxAllowedPacket, 2)
                : self::WRITE_BATCH_FALLBACK_BYTES;
        }

        return self::$writeBatchByteBudget;
    }

    /**
     * @param int[] $groupIds
     *
     * @throws Exception
     */
    private function deleteDocumentRows(string $table, array $groupIds, ?int $objectId, string $fieldname): void
    {
        foreach (array_chunk($groupIds, self::WRITE_BATCH_SIZE) as $chunk) {
            $this->db->executeStatement(
                'DELETE FROM `' . $table . '` WHERE `id` = ? AND `fieldname` = ? AND `groupId` IN (?)',
                [$objectId, $fieldname, $chunk],
                [ParameterType::INTEGER, ParameterType::STRING, ArrayParameterType::INTEGER]
            );
        }
    }

    /**
     * The key ids held by the items of an active group, which is the set the save resolves a key
     * config for.
     *
     * @param array $items structure: [groupId][keyId][language] = value
     * @param array<int, bool> $activeGroups
     *
     * @return int[]
     */
    private function getItemKeyIds(array $items, array $activeGroups): array
    {
        $keyIds = [];

        foreach ($items as $groupId => $keys) {
            if (!isset($activeGroups[$groupId])) {
                continue;
            }

            foreach (array_keys($keys) as $keyId) {
                $keyIds[] = (int) $keyId;
            }
        }

        return $keyIds;
    }

    /**
     * The key ids of the stored documents, which is the set the load resolves a key config for.
     *
     * @param array<int, array<int, mixed>> $documents
     *
     * @return int[]
     */
    private function getStoredKeyIds(array $documents): array
    {
        $keyIds = [];

        foreach ($documents as $keys) {
            foreach (array_keys($keys) as $keyId) {
                $keyIds[] = (int) $keyId;
            }
        }

        return $keyIds;
    }

    /**
     * The name of the document table of a class, for the migration that has to read and verify it.
     *
     * @internal
     */
    public function getDocumentTableName(string $classId): string
    {
        return self::DOCUMENT_TABLE_PREFIX . $classId;
    }

    /**
     * Removes the documents of a field name the class no longer carries.
     *
     * Which names it still carries is answered by the same recursive discovery the engine routing
     * uses, {@see Service::getClassificationStoreFieldDefinitions()}: a classification store may be
     * defined inside an object brick or a field collection, and its documents land in this table
     * under the field's own name all the same - while {@see ClassDefinition::getFieldDefinition()}
     * looks no further than the class's own fields and its localized ones, and would call every one
     * of those names gone.
     */
    private function cleanupTable(string $tableName, string $classId): void
    {
        $classDefinition = ClassDefinition::getByIdIgnoreCase($classId);
        if (!$classDefinition) {
            $this->logger->error("Classdefinition '" . $classId . "' not found. Please check table " . $tableName);

            return;
        }

        $liveFieldNames = Service::groupStoreIdsByFieldName(
            Service::getClassificationStoreFieldDefinitions($classDefinition->getFieldDefinitions())
        );

        $fieldNames = $this->db->fetchFirstColumn('SELECT DISTINCT fieldname FROM ' . $tableName);

        foreach ($fieldNames as $fieldName) {
            if (!isset($liveFieldNames[$fieldName])) {
                $this->logger->info("Field '" . $fieldName . "' of class '" . $classId . "' does not exist anymore. Cleaning " . $tableName);
                $this->db->delete($tableName, ['fieldname' => $fieldName]);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getTableNames(): array
    {
        $tableNames = $this->db->fetchAllAssociative("SHOW TABLES LIKE '" . self::DOCUMENT_TABLE_PREFIX . "%'");

        return array_map(static fn (array $table): string => (string) current($table), $tableNames);
    }
}
