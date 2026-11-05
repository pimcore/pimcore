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
use Pimcore\Tests\Support\Util\TestHelper;
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

        return array_map(static fn (array $descriptor): int => (int) $descriptor['id'], $descriptors);
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
