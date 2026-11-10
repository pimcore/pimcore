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
use Pimcore\Bundle\CoreBundle\Command\TranslateClassificationStoreIdentifiersCommand;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\GridConfigReferenceTranslator;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\ReferenceTranslatorInterface;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\TranslationReport;
use Pimcore\Db;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;
use Symfony\Component\Console\Tester\CommandTester;

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

    protected function tearDown(): void
    {
        foreach ($this->createdGridIds as $id) {
            Db::get()->delete(self::TABLE_GRID, ['id' => $id]);
        }
        $this->createdGridIds = [];

        $this->removeIdentifiedRows();

        parent::tearDown();
    }

    public function testTheCommandIsWiredWithTheCoreTranslator(): void
    {
        $translators = $this->injectedTranslators(
            Pimcore::getContainer()->get('test.service_container')->get(TranslateClassificationStoreIdentifiersCommand::class)
        );

        $this->assertCount(1, $translators, 'core ships exactly one reference translator');
        $this->assertInstanceOf(GridConfigReferenceTranslator::class, $translators[0]);
    }

    public function testAnotherBundlesTranslatorIsRunWithNoFurtherWiring(): void
    {
        $stub = new class() implements ReferenceTranslatorInterface {
            public int $calls = 0;

            public function getName(): string
            {
                return 'Stub surface';
            }

            public function translate(bool $dryRun): TranslationReport
            {
                $this->calls++;

                return new TranslationReport(0);
            }
        };

        $tagged = $this->injectedTranslators(
            Pimcore::getContainer()->get('test.service_container')->get(TranslateClassificationStoreIdentifiersCommand::class)
        );

        $tester = new CommandTester(new TranslateClassificationStoreIdentifiersCommand([...$tagged, $stub]));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame(1, $stub->calls);
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
        $gridId = $this->seedGrid($config);

        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($config, json_encode(json_decode($this->readConfig($gridId))));
        foreach ($tokens as $token) {
            $this->assertStringContainsString('gridconfigs row ' . $gridId . ' "' . $token . '"', $tester->getDisplay());
        }
        $this->assertStringContainsString('3 reference(s) unresolved.', $tester->getDisplay());
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
        $unresolvable = $this->seedGrid(json_encode(['columns' => [['key' => '~classificationstore~csstore~abc-def']]]));
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
     * @return array{grid: int}
     */
    private function seedFixture(): array
    {
        $storeId = $this->seedIdentifiedStore();
        $group = $this->seedIdentifiedRow(self::TABLE_GROUPS, $storeId, 'Group', 'hyphen-group');
        $key = $this->seedIdentifiedRow(self::TABLE_KEYS, $storeId, 'Key', 'hyphen-key');
        $token = '~classificationstore~csstore~' . $group . '-' . $key;

        $config = [
            'columns' => [['key' => 'id'], ['key' => $token]],
            'sort' => ['property' => $token, 'direction' => 'ASC'],
        ];

        return ['grid' => $this->seedGrid(json_encode($config))];
    }

    private function seedGrid(?string $config): int
    {
        $db = Db::get();
        $db->insert(self::TABLE_GRID, ['name' => 'translate test', 'type' => 'object', 'config' => $config]);
        $id = (int) $db->lastInsertId();
        $this->createdGridIds[] = $id;

        return $id;
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

    private function injectedTranslators(TranslateClassificationStoreIdentifiersCommand $command): array
    {
        return iterator_to_array(
            (new ReflectionProperty(TranslateClassificationStoreIdentifiersCommand::class, 'translators'))->getValue($command),
            false
        );
    }
}
