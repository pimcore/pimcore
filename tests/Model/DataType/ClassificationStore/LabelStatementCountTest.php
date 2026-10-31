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
