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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore;
use Pimcore\Bundle\CoreBundle\Command\PurgeClassificationStoreRelationsCommand;
use Pimcore\Cache;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Helper\Pimcore as PimcoreModule;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The opt-in sweep for stored descriptors whose target is gone: it reports by default and changes
 * nothing, it rewrites the affected documents only when the dry run is switched off, and a store on
 * an engine that stores no relation at all is named as skipped rather than walked.
 *
 * @group model.datatype.classificationstore
 */
class PurgeRelationsCommandTest extends AbstractClassificationStoreTest
{
    use MigrationServiceTrait;
    use RelationFixtureTrait;

    private const FIELDNAME = 'csstore';

    private const CLASS_NAME = 'csstore';

    private const DOCUMENT_TABLE = 'object_classificationstore_doc_csstore';

    private const METADATA_TABLE = 'object_classificationstore_meta_csstore';

    private const KEY_PREFIX = 'cs-purge-';

    private ?StoreConfig $eavStore = null;

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        Cache::disable();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        $this->deleteRelationFixture();

        $this->eavStore?->delete();
        $this->eavStore = null;

        TestHelper::cleanUp();

        parent::tearDown();
    }

    public function testTheDefaultRunReportsAndChangesNothing(): void
    {
        [$object, $groupId, $keyId, $kept, $removed] = $this->storeTwoTargetsAndDeleteOne();
        $before = $this->readDocument($object->getId(), $groupId);

        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('object ' . $object->getId(), $tester->getDisplay());
        $this->assertStringContainsString('DRY-RUN', $tester->getDisplay());
        $this->assertSame($before, $this->readDocument($object->getId(), $groupId));
        $this->assertSame([$kept, $removed], $this->storedTargetIds($object->getId(), $groupId, $keyId));
    }

    public function testTheForcedRunRewritesTheDocumentWithoutTheDeadDescriptor(): void
    {
        [$object, $groupId, $keyId, $kept] = $this->storeTwoTargetsAndDeleteOne();

        $tester = $this->runCommand(['--force' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame([$kept], $this->storedTargetIds($object->getId(), $groupId, $keyId));
    }

    public function testASecondForcedRunFindsNothingLeft(): void
    {
        $this->storeTwoTargetsAndDeleteOne();

        $this->runCommand(['--force' => true]);
        $second = $this->runCommand(['--force' => true]);

        $this->assertStringContainsString('Every stored Classification Store relation resolves.', $second->getDisplay());
    }

    public function testAStoreOnTheEavEngineIsReportedAsSkipped(): void
    {
        $this->eavStore = new StoreConfig();
        $this->eavStore->setName(uniqid('csPurgeEavStore', false));
        $this->eavStore->save();

        $display = $this->runCommand()->getDisplay();

        $this->assertStringContainsString('Skipped, the engine stores no relation', $display);
        $this->assertStringContainsString((string) $this->eavStore->getId(), $display);
    }

    /**
     * The per-relation column values of an advanced relation are addressed by the position of their
     * entry in the value, and a purge reindexes the entries that survive it. The stored rows have to
     * be reindexed with them: left where they were, the surviving relation would come back carrying
     * the column values of the one that was removed.
     */
    public function testTheForcedRunReindexesThePerRelationColumnValuesOfTheSurvivors(): void
    {
        $group = $this->createRelationFixture(['csAdvancedManyToManyObject']);
        $keyId = $this->getRelationKeyId('csAdvancedManyToManyObject');

        $removed = TestHelper::createEmptyObject(self::KEY_PREFIX . 'metaremoved');
        $kept = TestHelper::createEmptyObject(self::KEY_PREFIX . 'metakept');

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey(self::KEY_PREFIX . 'metaowner');
        $object->setPublished(true);
        $object->save();

        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [
            $this->metadataItem($removed, 'of-the-removed'),
            $this->metadataItem($kept, 'of-the-kept'),
        ]);
        $object->save();

        $removed->delete();

        $this->runCommand(['--force' => true]);

        $this->assertSame([$kept->getId()], $this->storedTargetIds($object->getId(), (int) $group->getId(), $keyId));
        $this->assertSame(
            [['index' => 0, 'column' => self::RELATION_COLUMN, 'data' => 'of-the-kept']],
            $this->readMetadata($object->getId(), (int) $group->getId(), $keyId),
            'the survivor keeps its own column values at the position it was reindexed to'
        );

        $read = Csstore::getById($object->getId(), ['force' => true])
            ->getCsstore()
            ->getLocalizedKeyValue((int) $group->getId(), $keyId);

        $this->assertCount(1, $read);
        $this->assertSame('of-the-kept', $read[0]->getData()[self::RELATION_COLUMN]);
    }

    /**
     * The sweep reads a batch of rows and writes each of them back afterwards, so a save made in
     * between must not be discarded. The write is therefore conditional on the checksum the row was
     * read with, which is the column the save path keeps for exactly that comparison.
     */
    public function testTheForcedRunWritesADocumentOnlyWhileItIsStillTheOneItRead(): void
    {
        $this->storeTwoTargetsAndDeleteOne();

        $this->resetStatements();
        $this->runCommand(['--force' => true]);

        $updates = array_filter(
            $this->grabStatements(),
            static fn (string $sql): bool => str_starts_with($sql, 'UPDATE `' . self::DOCUMENT_TABLE . '`')
        );

        $this->assertNotSame([], $updates, 'precondition: the forced run rewrites the document');

        foreach ($updates as $sql) {
            $this->assertStringContainsString(
                '`checksum` <=> ?',
                $sql,
                'the rewrite has to be a compare-and-swap against the checksum the row was read with'
            );
        }
    }

    /**
     * @return array{0: Csstore, 1: int, 2: int, 3: int, 4: int} the object, group, key, the
     *         surviving target and the one that was deleted
     */
    private function storeTwoTargetsAndDeleteOne(): array
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');

        $kept = TestHelper::createEmptyObject(self::KEY_PREFIX . 'kept');
        $removed = TestHelper::createEmptyObject(self::KEY_PREFIX . 'removed');

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey(self::KEY_PREFIX . 'owner');
        $object->setPublished(true);
        $object->save();

        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$kept, $removed]);
        $object->save();

        $removedId = $removed->getId();
        $removed->delete();

        return [$object, (int) $group->getId(), $keyId, $kept->getId(), $removedId];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input = []): CommandTester
    {
        $resolver = Pimcore::getContainer()->get(EngineResolver::class);
        $documentEngine = $resolver->getEngines()['document'] ?? null;
        $this->assertInstanceOf(DocumentEngine::class, $documentEngine);

        $command = new PurgeClassificationStoreRelationsCommand(
            $this->getMigrationService(),
            $resolver,
            $documentEngine
        );

        $tester = new CommandTester($command);
        $tester->execute(['--class' => self::CLASS_NAME] + $input);

        return $tester;
    }

    /**
     * @return int[]
     */
    private function storedTargetIds(int $objectId, int $groupId, int $keyId): array
    {
        $document = json_decode($this->readDocument($objectId, $groupId), true);
        $descriptors = $document['k'][$keyId][0]['d'] ?? [];

        return array_map(
            static fn (array $descriptor): int => (int) ($descriptor['element']['id'] ?? $descriptor['id']),
            $descriptors
        );
    }

    private function metadataItem(ElementInterface $target, string $data): ObjectMetadata
    {
        $item = new ObjectMetadata('csAdvancedManyToManyObject', [self::RELATION_COLUMN], $target);
        $item->setData([self::RELATION_COLUMN => $data]);

        return $item;
    }

    /**
     * @return array<array{index: int, column: string, data: string}>
     */
    private function readMetadata(int $objectId, int $groupId, int $keyId): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT `index`, `column`, `data` FROM ' . Db::get()->quoteIdentifier(self::METADATA_TABLE)
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ? AND `keyId` = ?'
            . ' ORDER BY `index`, `column`',
            [$objectId, self::FIELDNAME, $groupId, $keyId]
        );

        return array_map(static fn (array $row): array => [
            'index' => (int) $row['index'],
            'column' => (string) $row['column'],
            'data' => (string) $row['data'],
        ], $rows);
    }

    /**
     * Starts recording the statements the command issues.
     */
    private function resetStatements(): void
    {
        $this->getDebugDataHolder()->reset();
    }

    /**
     * The statements issued since {@see self::resetStatements()}, on one line each.
     *
     * @return string[]
     */
    private function grabStatements(): array
    {
        $statements = [];

        foreach ($this->getDebugDataHolder()->getData() as $queries) {
            foreach ($queries as $query) {
                $statements[] = trim((string) preg_replace('/\s+/', ' ', (string) $query['sql']));
            }
        }

        return $statements;
    }

    private function getDebugDataHolder(): DebugDataHolder
    {
        $container = $this->getModule('\\' . PimcoreModule::class)->_getContainer();

        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('The Doctrine debug data holder is only registered when the kernel runs in debug mode.');
        }

        return $container->get('doctrine.debug_data_holder');
    }

    private function readDocument(int $objectId, int $groupId): string
    {
        return (string) Db::get()->fetchOne(
            'SELECT `values` FROM ' . Db::get()->quoteIdentifier(self::DOCUMENT_TABLE)
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ?',
            [$objectId, self::FIELDNAME, $groupId]
        );
    }
}
