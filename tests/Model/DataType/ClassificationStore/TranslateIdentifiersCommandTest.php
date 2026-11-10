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

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Bundle\CoreBundle\Command\TranslateClassificationStoreIdentifiersCommand;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\GridConfigReferenceTranslator;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\ReferenceTranslatorInterface;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel;
use Pimcore\Tests\Support\Helper\ClassificationStore\StubReferenceTranslator;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;
use Symfony\Component\Console\Tester\CommandTester;
use Traversable;

/**
 * @group model.classificationstore.identifier
 */
final class TranslateIdentifiersCommandTest extends ModelTestCase
{
    use IdentifiedRowsFixtureTrait;

    private const TABLE_GROUPS = 'classificationstore_groups';

    private const TABLE_KEYS = 'classificationstore_keys';

    private const TABLE_GRID = 'gridconfigs';

    private const FIELD = 'csstore';

    private array $createdGridIds = [];

    private array $createdClassIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdGridIds as $id) {
            Db::get()->delete(self::TABLE_GRID, ['id' => $id]);
        }
        $this->createdGridIds = [];

        foreach ($this->createdClassIds as $classId) {
            ClassDefinition::getById($classId)?->delete();
        }
        $this->createdClassIds = [];

        $this->removeIdentifiedRows();

        parent::tearDown();
    }

    public function testTheCommandIsWiredWithTheCoreTranslator(): void
    {
        $translators = $this->injectedTranslators($this->wiredCommand());

        $core = array_values(array_filter(
            $translators,
            static fn (ReferenceTranslatorInterface $translator): bool => !$translator instanceof StubReferenceTranslator
        ));

        $this->assertCount(1, $core, 'core ships exactly one reference translator');
        $this->assertInstanceOf(GridConfigReferenceTranslator::class, $core[0]);
    }

    /**
     * The extension point, end to end through the container: the test application registers
     * {@see StubReferenceTranslator} as a plain service carrying no tag of its own, so the only thing that can
     * put it on the command is the autoconfiguration of
     * {@see \Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension}. The iterator the booted
     * container injected is asserted, and then that running the command really runs it.
     */
    public function testAnotherBundlesTranslatorIsRunWithNoFurtherWiring(): void
    {
        $command = $this->wiredCommand();
        $translators = $this->injectedTranslators($command);

        $stubs = array_values(array_filter(
            $translators,
            static fn (ReferenceTranslatorInterface $translator): bool => $translator instanceof StubReferenceTranslator
        ));

        $this->assertCount(1, $stubs, 'the tagged iterator must carry the untagged service of the test application');
        $this->assertContainsOnlyInstancesOf(ReferenceTranslatorInterface::class, $translators);

        $stub = $stubs[0];
        $callsBefore = $stub->calls;

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($callsBefore + 1, $stub->calls, 'the command runs the other bundle\'s translator');
        $this->assertFalse($stub->dryRun);
        $this->assertStringContainsString('Stub surface', $tester->getDisplay());
        $this->assertStringContainsString('Grid configurations', $tester->getDisplay());
    }

    public function testTokensAreRewrittenAndNothingElseIsTouched(): void
    {
        $fixture = $this->seedFixture();
        $untouched = $this->seedGrid('{"columns":[{"key":"id"},{"key":"#helper"}],"empty":{},"list":[]}');
        $withoutConfig = $this->seedGrid(null);

        $before = $this->readConfig($untouched);
        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($before, $this->readConfig($untouched));
        $this->assertNull(
            Db::get()->fetchOne('SELECT config FROM gridconfigs WHERE id = ?', [$withoutConfig]),
            'a row carrying no config at all is left alone'
        );
        $this->assertStringContainsString('0 reference(s) unresolved.', $tester->getDisplay());

        $config = json_decode($this->readConfig($fixture['grid']), true);
        $this->assertSame('~classificationstore~csstore~hyphen-group~hyphen-key', $config['columns'][1]['key']);
        $this->assertSame('~classificationstore~csstore~hyphen-group~hyphen-key', $config['sort']['property']);
        $this->assertSame('id', $config['columns'][0]['key']);
        $this->assertSame('ASC', $config['sort']['direction']);
        $this->assertStringContainsString('2 reference(s) translated.', $tester->getDisplay());
    }

    public function testUntranslatableTokensAreLeftAndReported(): void
    {
        $storeId = $this->seedIdentifiedStore();
        $group = $this->seedIdentifiedRow(self::TABLE_GROUPS, $storeId, 'G', 'g-one');
        $noIdentifierKey = $this->seedIdentifiedRow(self::TABLE_KEYS, $storeId, 'K', null);

        $tokens = [
            '~classificationstore~csstore~' . $group . '-' . $noIdentifierKey,
            '~classificationstore~csstore~' . ($group + 100000) . '-' . $noIdentifierKey,
            '~classificationstore~csstore~abc-def',
        ];
        $config = json_encode(['columns' => array_map(static fn (string $t): array => ['key' => $t], $tokens)]);
        $gridId = $this->seedGrid($config, $this->seedClass($storeId));

        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($config, json_encode(json_decode($this->readConfig($gridId))));
        foreach ($tokens as $token) {
            $this->assertStringContainsString('gridconfigs row ' . $gridId . ' "' . $token . '"', $tester->getDisplay());
        }
        $this->assertStringContainsString('the group or the key has no identifier', $tester->getDisplay());
        $this->assertStringContainsString('the group or the key does not exist', $tester->getDisplay());
        $this->assertStringContainsString('the group and key are not both numeric ids', $tester->getDisplay());
        $this->assertStringContainsString('3 reference(s) unresolved.', $tester->getDisplay());
    }

    /**
     * An identifier is unique per store only, and both readers resolve the rewritten token inside the store
     * of the field the token names. A token whose ids belong to another store would therefore be rewritten
     * into an identifier pair naming a different group and key - here one carrying the very same identifiers
     * in the field's own store - so it is refused instead.
     */
    public function testATokenOfAnotherStoreIsLeftAndReported(): void
    {
        $otherStoreId = $this->seedIdentifiedStore();
        $otherGroup = $this->seedIdentifiedRow(self::TABLE_GROUPS, $otherStoreId, 'Dimensions', 'dimensions');
        $otherKey = $this->seedIdentifiedRow(self::TABLE_KEYS, $otherStoreId, 'Width', 'width');

        $fieldStoreId = $this->seedIdentifiedStore();
        $this->seedIdentifiedRow(self::TABLE_GROUPS, $fieldStoreId, 'Dimensions', 'dimensions');
        $this->seedIdentifiedRow(self::TABLE_KEYS, $fieldStoreId, 'Width', 'width');

        $token = '~classificationstore~csstore~' . $otherGroup . '-' . $otherKey;
        $config = json_encode(['columns' => [['key' => $token]]]);
        $gridId = $this->seedGrid($config, $this->seedClass($fieldStoreId));

        $tester = $this->runCommand();

        $this->assertSame($config, json_encode(json_decode($this->readConfig($gridId))));
        $this->assertStringContainsString(
            'gridconfigs row ' . $gridId . ' "' . $token . '": the group or the key is not in the store of the field',
            $tester->getDisplay()
        );
        $this->assertStringContainsString('0 reference(s) translated.', $tester->getDisplay());
        $this->assertStringContainsString('1 reference(s) unresolved.', $tester->getDisplay());
    }

    /**
     * The store of the field is resolved through the class the row carries, so a row naming no class at all
     * and a row naming one that is not there carry nothing the identifiers could be scoped to.
     */
    public function testATokenWhoseFieldStoreDoesNotResolveIsLeftAndReported(): void
    {
        $storeId = $this->seedIdentifiedStore();
        $group = $this->seedIdentifiedRow(self::TABLE_GROUPS, $storeId, 'Group', 'the-group');
        $key = $this->seedIdentifiedRow(self::TABLE_KEYS, $storeId, 'Key', 'the-key');

        $token = '~classificationstore~csstore~' . $group . '-' . $key;
        $config = json_encode(['columns' => [['key' => $token]]]);
        $withoutClass = $this->seedGrid($config);
        $unknownClass = $this->seedGrid($config, 'NoSuchClassAtAll');

        $tester = $this->runCommand();

        foreach ([$withoutClass, $unknownClass] as $gridId) {
            $this->assertSame($config, json_encode(json_decode($this->readConfig($gridId))));
            $this->assertStringContainsString(
                'gridconfigs row ' . $gridId . ' "' . $token . '": the store of the classification store field does not resolve',
                $tester->getDisplay()
            );
        }
        $this->assertStringContainsString('0 reference(s) translated.', $tester->getDisplay());
        $this->assertStringContainsString('2 reference(s) unresolved.', $tester->getDisplay());
    }

    /**
     * The write guard: the `config` the row was read with. A configuration saved between the read and the
     * write keeps it, and both of the row's tokens are reported as themselves rather than the row being
     * reported once with no token in it.
     */
    public function testAConfigurationSavedWhileTheCommandRunsIsNotOverwritten(): void
    {
        $fixture = $this->seedFixture();
        $saved = '{"columns":[{"key":"id"}],"savedBy":"the user"}';

        $tester = new CommandTester(new TranslateClassificationStoreIdentifiersCommand([
            new GridConfigReferenceTranslator($this->connectionWritingTo($fixture['grid'], $saved)),
        ]));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($saved, $this->readConfig($fixture['grid']), 'the configuration the user saved is kept');

        $display = $tester->getDisplay();
        $this->assertStringContainsString('0 reference(s) translated.', $display);
        $this->assertStringContainsString('2 reference(s) unresolved.', $display);
        $this->assertStringContainsString(
            'gridconfigs row ' . $fixture['grid'] . ' "' . $fixture['token'] . '": the row was changed while the command was running',
            $display
        );
        $this->assertSame(
            2,
            substr_count($display, 'the row was changed while the command was running'),
            'every token of the row that was not written is reported as itself'
        );
    }

    public function testASecondRunWritesNothing(): void
    {
        $fixture = $this->seedFixture();

        $this->runCommand();
        $afterFirst = $this->readConfig($fixture['grid']);
        $second = $this->runCommand();

        $this->assertSame($afterFirst, $this->readConfig($fixture['grid']));
        $this->assertStringContainsString('0 reference(s) translated.', $second->getDisplay());
        $this->assertStringContainsString('0 reference(s) unresolved.', $second->getDisplay());
    }

    public function testDryRunReportsTheSameAndWritesNothing(): void
    {
        $fixture = $this->seedFixture();
        $unresolvable = $this->seedGrid(
            json_encode(['columns' => [['key' => '~classificationstore~csstore~abc-def']]]),
            $fixture['class']
        );
        $before = $this->readConfig($fixture['grid']);
        $beforeUnresolvable = $this->readConfig($unresolvable);

        $dry = $this->runCommand(['--dry-run' => true]);

        $this->assertSame($before, $this->readConfig($fixture['grid']));
        $this->assertSame($beforeUnresolvable, $this->readConfig($unresolvable));
        $this->assertStringContainsString('2 reference(s) translated.', $dry->getDisplay());
        $this->assertStringContainsString('1 reference(s) unresolved.', $dry->getDisplay());

        $real = $this->runCommand();

        $this->assertStringContainsString('2 reference(s) translated.', $real->getDisplay());
        $this->assertStringContainsString('1 reference(s) unresolved.', $real->getDisplay());
        $this->assertNotSame($before, $this->readConfig($fixture['grid']));
        $this->assertSame($beforeUnresolvable, $this->readConfig($unresolvable));
    }

    public function testOnlyTheConfigColumnIsWritten(): void
    {
        $fixture = $this->seedFixture();
        $before = Db::get()->fetchAssociative('SELECT * FROM gridconfigs WHERE id = ?', [$fixture['grid']]);

        $this->runCommand();

        $after = Db::get()->fetchAssociative('SELECT * FROM gridconfigs WHERE id = ?', [$fixture['grid']]);
        unset($before['config'], $after['config']);
        $this->assertSame($before, $after);
    }

    /**
     * @return array{grid: int, class: string, token: string}
     */
    private function seedFixture(): array
    {
        $storeId = $this->seedIdentifiedStore();
        $group = $this->seedIdentifiedRow(self::TABLE_GROUPS, $storeId, 'Group', 'hyphen-group');
        $key = $this->seedIdentifiedRow(self::TABLE_KEYS, $storeId, 'Key', 'hyphen-key');
        $token = '~classificationstore~csstore~' . $group . '-' . $key;
        $classId = $this->seedClass($storeId);

        $config = [
            'columns' => [['key' => 'id'], ['key' => $token]],
            'sort' => ['property' => $token, 'direction' => 'ASC'],
        ];

        return ['grid' => $this->seedGrid(json_encode($config), $classId), 'class' => $classId, 'token' => $token];
    }

    /**
     * A class carrying a classification store field named {@see self::FIELD} that points at the given store:
     * the token names that field, and the command resolves the store of the field through this class.
     */
    private function seedClass(int $storeId): string
    {
        $field = new ClassificationstoreField();
        $field->setName(self::FIELD);
        $field->setTitle(self::FIELD);
        $field->setStoreId($storeId);

        $panel = (new Panel())->setName('MyLayout');
        $panel->addChild($field);
        $tabPanel = (new Tabpanel())->setName('Layout');
        $tabPanel->addChild($panel);
        $root = (new Panel())->setName('root');
        $root->addChild($tabPanel);

        $name = 'TranslateTarget' . bin2hex(random_bytes(6));

        $class = new ClassDefinition();
        $class->setId($name);
        $class->setName($name);
        $class->setUserOwner(1);
        $class->setLayoutDefinitions($root);
        $class->save();

        $this->createdClassIds[] = $name;

        return $name;
    }

    private function seedGrid(?string $config, ?string $classId = null): int
    {
        $db = Db::get();
        $db->insert(self::TABLE_GRID, [
            'name' => 'translate test',
            'type' => 'object',
            'classId' => $classId,
            'config' => $config,
        ]);
        $id = (int) $db->lastInsertId();
        $this->createdGridIds[] = $id;

        return $id;
    }

    /**
     * A connection that saves `$config` over the given row the moment the command's own read hands that row
     * out - the save the write guard has to lose against.
     */
    private function connectionWritingTo(int $rowId, string $config): Connection
    {
        return new class(Db::get(), $rowId, $config) extends Connection {
            public function __construct(
                private readonly Connection $inner,
                private readonly int $rowId,
                private readonly string $config
            ) {
            }

            public function iterateAssociative(string $query, array $params = [], array $types = []): Traversable
            {
                foreach ($this->inner->iterateAssociative($query, $params, $types) as $row) {
                    if (isset($row['id']) && (int) $row['id'] === $this->rowId) {
                        $this->inner->executeStatement(
                            'UPDATE `gridconfigs` SET `config` = ? WHERE `id` = ?',
                            [$this->config, $this->rowId]
                        );
                    }

                    yield $row;
                }
            }

            public function fetchAssociative(string $query, array $params = [], array $types = []): array|false
            {
                return $this->inner->fetchAssociative($query, $params, $types);
            }

            public function executeStatement(string $sql, array $params = [], array $types = []): int|string
            {
                return $this->inner->executeStatement($sql, $params, $types);
            }
        };
    }

    private function readConfig(int $id): string
    {
        return (string) Db::get()->fetchOne('SELECT config FROM gridconfigs WHERE id = ?', [$id]);
    }

    private function runCommand(array $input = []): CommandTester
    {
        $tester = new CommandTester(
            new TranslateClassificationStoreIdentifiersCommand([new GridConfigReferenceTranslator(Db::get())])
        );
        $tester->execute($input);

        return $tester;
    }

    private function wiredCommand(): TranslateClassificationStoreIdentifiersCommand
    {
        return Pimcore::getContainer()->get('test.service_container')
            ->get(TranslateClassificationStoreIdentifiersCommand::class);
    }

    private function injectedTranslators(TranslateClassificationStoreIdentifiersCommand $command): array
    {
        return iterator_to_array(
            (new ReflectionProperty(TranslateClassificationStoreIdentifiersCommand::class, 'translators'))->getValue($command),
            false
        );
    }
}
