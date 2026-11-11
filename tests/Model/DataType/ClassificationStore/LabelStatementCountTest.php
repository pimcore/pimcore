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
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\Label\Dao as LabelDao;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolver;

/**
 * The labels of a whole set of entities are read in one batch, and a second resolution of the same
 * entity within the request reads nothing at all.
 *
 * @group model.datatype.classificationstore
 */
class LabelStatementCountTest extends AbstractBatchedRelationTest
{
    use LabelFixtureTrait;

    private const KEY_COUNT = 50;

    private const LANGUAGE = 'de';

    private const LANGUAGE_WITH_TRANSITIVE_FALLBACK = 'de_AT';

    private const SYSTEM_SETTINGS_PATTERN = '/settings_store/i';

    protected function tearDown(): void
    {
        Db::get()->executeStatement('DELETE FROM `' . LabelDao::TABLE_NAME_LABELS . '`');
        $this->labelResolver()->reset();

        parent::tearDown();
    }

    public function testResolvingManyKeysCostsOneStatementAndTheRepeatCostsNone(): void
    {
        $keys = $this->createLabelledKeys(self::LANGUAGE);

        $resolver = $this->labelResolver();
        $resolver->reset();

        $entityIds = array_map(static fn ($keyConfig): int => (int) $keyConfig->getId(), $keys);

        $this->startMeasurement();
        $resolver->preload(LabelEntityType::Key, $entityIds, self::LANGUAGE);
        foreach ($keys as $keyConfig) {
            $this->assertNotNull($resolver->getTitle($keyConfig, self::LANGUAGE));
        }

        $this->assertSame(
            1,
            $this->countReadStatements(),
            'resolving ' . self::KEY_COUNT . ' keys of one type and one language must cost one statement'
        );

        $this->startMeasurement();
        foreach ($keys as $keyConfig) {
            $this->assertNotNull($resolver->getTitle($keyConfig, self::LANGUAGE));
        }

        $this->assertSame(0, $this->countReadStatements(), 'a second resolution within the request reads nothing');
    }

    /**
     * `de_AT` falls back to `de`, which falls back to `en`, and the default language follows those:
     * the longer chain has to be folded into the one statement rather than read language by language.
     */
    public function testATransitiveLanguageChainStillCostsOneStatement(): void
    {
        $keys = $this->createLabelledKeys(self::LANGUAGE);

        $resolver = $this->labelResolver();
        $resolver->reset();

        $entityIds = array_map(static fn ($keyConfig): int => (int) $keyConfig->getId(), $keys);

        $this->startMeasurement();
        $resolver->preload(LabelEntityType::Key, $entityIds, self::LANGUAGE_WITH_TRANSITIVE_FALLBACK);
        foreach ($keys as $keyConfig) {
            $this->assertNotNull($resolver->getTitle($keyConfig, self::LANGUAGE_WITH_TRANSITIVE_FALLBACK));
        }

        $this->assertSame(
            1,
            $this->countReadStatements(),
            'the whole language chain of ' . self::KEY_COUNT . ' keys must cost one statement'
        );
    }

    /**
     * The language chain is a pure function of the language and the system settings, and
     * `walk()` asks for it BEFORE consulting the label memo — so without a memo of its own
     * every resolved key rebuilt it, and each rebuild reads the system settings. Measured on
     * a Studio object layout carrying 600 classification store values: 1,266
     * `SELECT * FROM settings_store`.
     *
     * The memo is only safe because `reset()` drops it, so this pins both halves: the repeat
     * reads nothing, and the reset makes the next resolution read again. Delete the
     * `languageChains` line from `reset()` and the third assertion fails.
     */
    public function testTheLanguageChainIsMemoisedPerRequestAndRebuiltAfterReset(): void
    {
        $keys = $this->createLabelledKeys(self::LANGUAGE);

        $resolver = $this->labelResolver();
        $resolver->reset();

        $entityIds = array_map(static fn ($keyConfig): int => (int) $keyConfig->getId(), $keys);

        $this->startMeasurement();
        $resolver->preload(LabelEntityType::Key, $entityIds, self::LANGUAGE_WITH_TRANSITIVE_FALLBACK);
        foreach ($keys as $keyConfig) {
            $resolver->getTitle($keyConfig, self::LANGUAGE_WITH_TRANSITIVE_FALLBACK);
        }
        $whenBuilt = count($this->grabStatementsMatching(self::SYSTEM_SETTINGS_PATTERN));

        if ($whenBuilt === 0) {
            // Not a failure: an installation serving the system settings from a config file
            // rather than the database gives this harness nothing to count, and a red build
            // would be reporting the environment, not the code.
            $this->markTestSkipped(
                'the system settings are not read from the database here, so a chain rebuild '
                . 'is not observable through the statement log'
            );
        }

        $this->startMeasurement();
        foreach ($keys as $keyConfig) {
            $resolver->getTitle($keyConfig, self::LANGUAGE_WITH_TRANSITIVE_FALLBACK);
        }

        $this->assertSame(
            0,
            count($this->grabStatementsMatching(self::SYSTEM_SETTINGS_PATTERN)),
            'resolving the same language again within the request must read nothing'
        );

        $resolver->reset();

        $this->startMeasurement();
        foreach ($keys as $keyConfig) {
            $resolver->getTitle($keyConfig, self::LANGUAGE_WITH_TRANSITIVE_FALLBACK);
        }

        $this->assertGreaterThan(
            0,
            count($this->grabStatementsMatching(self::SYSTEM_SETTINGS_PATTERN)),
            'reset() must drop the memoised chains, so a settings change after the boundary '
            . 'is picked up by the next resolution'
        );
    }

    /**
     * @return KeyConfig[]
     */
    private function createLabelledKeys(string $language): array
    {
        $keys = [];

        for ($index = 0; $index < self::KEY_COUNT; $index++) {
            $keyConfig = $this->createKey('label' . $index);
            $this->writeLabel(
                LabelEntityType::Key,
                (int) $keyConfig->getId(),
                $language,
                'Titel ' . $index,
                null
            );

            $keys[] = $keyConfig;
        }

        return $keys;
    }

    private function labelResolver(): LabelResolver
    {
        return Pimcore::getContainer()->get(LabelResolver::class);
    }
}
