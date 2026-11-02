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
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolver;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * The key's own title column: that an explicitly set title is what is stored, that the definition's
 * title only seeds a key which was neither loaded nor titled, that the seed is recomputed on every
 * save rather than latching, and that a save with nothing to seed leaves the stored title alone.
 *
 * @group model.datatype.classificationstore
 */
final class KeyTitleTest extends ModelTestCase
{
    use LabelFixtureTrait;

    private const TABLE_KEYS = KeyConfigDao::TABLE_NAME_KEYS;

    private const TITLE_DE = 'Bauhöhe';

    private const TITLE_EN = 'Height';

    private const TITLE_SECOND = 'Height2';

    private const TITLE_DEPTH = 'Depth';

    private const LANGUAGE_DE = 'de';

    protected function tearDown(): void
    {
        $this->deleteLabelFixtures();

        parent::tearDown();
    }

    /**
     * The call order `KeyRepository::update()` uses: the title the caller set, and the definition
     * blob of the same call still carrying the title it was built with.
     */
    public function testAnExplicitlySetTitleWinsOverTheDefinitionBlob(): void
    {
        $key = $this->createKey();
        $key->setTitle(self::TITLE_DE);
        $key->setDefinition($this->definitionBlob(self::TITLE_EN));
        $key->save();

        $id = (int) $key->getId();

        $this->assertSame(self::TITLE_DE, $this->readTitle($id));
        $this->assertSame(self::TITLE_DE, KeyConfig::getById($id, true)?->getTitle());

        $loaded = KeyConfig::getById($id, true);
        $this->assertNotNull($loaded);
        $loaded->setTitle(self::TITLE_SECOND);
        $loaded->save();

        $this->assertSame(self::TITLE_SECOND, $this->readTitle($id));
        $this->assertSame(
            self::TITLE_EN,
            $this->readDefinitionTitle($id),
            'the blob is unchanged throughout: the title is stored next to it, not in it'
        );
    }

    /**
     * Mirrors RelationMetadataTest::testASavedFreshRelationKeepsStoredPerAssignmentData: a model
     * built with `new` carries no title of its own, so it must not clear the stored one.
     */
    public function testAFreshKeyConfigSaveKeepsTheStoredTitle(): void
    {
        $key = $this->createKey();
        $key->setTitle(self::TITLE_DE);
        $key->save();

        $id = (int) $key->getId();
        $this->assertSame(self::TITLE_DE, $this->readTitle($id));

        $fresh = $this->freshKeyFor($key, $this->definitionBlob(null));
        $fresh->save();

        $this->assertSame(self::TITLE_DE, $this->readTitle($id), 'a save with nothing to seed writes no title');

        // the same fresh model, so the column is skipped rather than unwritable from that path
        $fresh->setTitle('Other');
        $fresh->save();

        $this->assertSame('Other', $this->readTitle($id));
    }

    /**
     * The two "nothing to seed" blobs a field definition cannot produce but an import can: a title
     * serialised as an empty string, and one that is not a string at all.
     */
    public function testABlobTitleThatIsEmptyOrNotAStringIsNothingToSeed(): void
    {
        $key = $this->createKey();
        $key->setTitle(self::TITLE_DE);
        $key->save();

        $id = (int) $key->getId();

        foreach ([$this->definitionBlob(''), '{"title":42}'] as $blob) {
            $this->freshKeyFor($key, $blob)->save();

            $this->assertSame(self::TITLE_DE, $this->readTitle($id));
        }
    }

    /**
     * The creation path of
     * doc/03_Objects/01_Object_Classes/01_Data_Types/15_Classification_Store.md, which sets no
     * title of its own and relies on the definition's one.
     */
    public function testTheDocumentedCreationPathSeedsTheTitleFromTheDefinition(): void
    {
        $key = $this->createKeyFromDocumentedPath();

        $this->assertSame(self::TITLE_EN, $this->readTitle((int) $key->getId()));
        $this->assertSame(self::TITLE_EN, $key->getTitle());
    }

    public function testTheSeedIsRecomputedOnEverySaveUntilTheTitleIsSetOrLoaded(): void
    {
        $seeded = $this->createKeyFromDocumentedPath();
        $seeded->setDefinition($this->definitionBlob(self::TITLE_DEPTH));
        $seeded->save();

        $this->assertSame(
            self::TITLE_DEPTH,
            $this->readTitle((int) $seeded->getId()),
            'the seed does not latch on the title the first definition carried'
        );

        $untitled = $this->createKey();
        $untitled->setDefinition($this->definitionBlob(null));
        $untitled->save();

        $this->assertSame('', $this->readTitle((int) $untitled->getId()));

        $untitled->setDefinition($this->definitionBlob(self::TITLE_EN));
        $untitled->save();

        $this->assertSame(self::TITLE_EN, $this->readTitle((int) $untitled->getId()));
    }

    public function testALoadedKeyNoLongerTakesItsTitleFromTheDefinition(): void
    {
        $key = $this->createKey();
        $key->setTitle(self::TITLE_DE);
        $key->save();

        $id = (int) $key->getId();

        $loaded = KeyConfig::getById($id, true);
        $this->assertNotNull($loaded);
        $loaded->setDefinition($this->definitionBlob(self::TITLE_EN));
        $loaded->save();

        $this->assertSame(self::TITLE_DE, $this->readTitle($id), 'a definition edit does not rewrite the title');

        $loaded->setDescription('Die Bauhöhe');
        $loaded->save();
        $this->assertSame(self::TITLE_DE, $this->readTitle($id));

        $loaded->setType('textarea');
        $loaded->save();
        $this->assertSame(self::TITLE_DE, $this->readTitle($id));

        // the column is still writable from the load path
        $loaded->setTitle(self::TITLE_EN);
        $loaded->save();

        $this->assertSame(self::TITLE_EN, $this->readTitle($id));
    }

    public function testAClearedTitleIsStoredEmptyAndTheChainFallsThroughToTheName(): void
    {
        $key = $this->createKey();
        $key->setTitle(self::TITLE_DE);
        $key->save();

        $id = (int) $key->getId();

        $key->setTitle('');
        $key->save();

        $this->assertSame('', $this->readTitle($id));

        $reloaded = KeyConfig::getById($id, true);
        $this->assertNotNull($reloaded);
        $this->assertSame('', $reloaded->getTitle());
        $this->assertSame(
            $reloaded->getName(),
            Pimcore::getContainer()->get(LabelResolver::class)->getTitle($reloaded, self::LANGUAGE_DE),
            'a key with no label rows and an empty title falls through to its technical name'
        );
    }

    private function createKey(): KeyConfig
    {
        $key = $this->createEntity(LabelEntityType::Key);
        $this->assertInstanceOf(KeyConfig::class, $key);

        return $key;
    }

    /**
     * The same key as a model built with `new`, which carries no title of its own.
     */
    private function freshKeyFor(KeyConfig $key, string $definition): KeyConfig
    {
        $fresh = new KeyConfig();
        $fresh->setId((int) $key->getId());
        $fresh->setStoreId($key->getStoreId());
        $fresh->setName($key->getName());
        $fresh->setType('input');
        $fresh->setDefinition($definition);

        return $fresh;
    }

    /**
     * A key built the way the documentation builds one: a definition carrying a title, and no
     * KeyConfig::setTitle() call.
     */
    private function createKeyFromDocumentedPath(): KeyConfig
    {
        $definition = new QuantityValue();
        $definition->setName('height');
        $definition->setTitle(self::TITLE_EN);

        $key = new KeyConfig();
        $key->setStoreId($this->createStore()->getId());
        $key->setName(uniqid('documentedkey', true));
        $key->setDescription('The height of the article');
        $key->setEnabled(true);
        $key->setType($definition->getFieldtype());
        $key->setDefinition(json_encode($definition));
        $key->save();

        return $key;
    }

    /**
     * A definition blob as the editor stores one. A null title is what a definition that was never
     * given one serialises to, which is nothing to seed.
     */
    private function definitionBlob(?string $title): string
    {
        $definition = new QuantityValue();
        $definition->setName('height');
        if ($title !== null) {
            $definition->setTitle($title);
        }

        return json_encode($definition);
    }

    private function readTitle(int $id): string
    {
        return (string) Db::get()->fetchOne(
            'SELECT `title` FROM `' . self::TABLE_KEYS . '` WHERE `id` = ?',
            [$id]
        );
    }

    private function readDefinitionTitle(int $id): ?string
    {
        $definition = json_decode(
            (string) Db::get()->fetchOne(
                'SELECT `definition` FROM `' . self::TABLE_KEYS . '` WHERE `id` = ?',
                [$id]
            ),
            true
        );

        return is_array($definition) ? $definition['title'] ?? null : null;
    }
}
