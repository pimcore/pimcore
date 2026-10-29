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
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\Label;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The title and description chains. The test environment configures `de` to fall back to `en`
 * (tests/Support/Resources/system_settings.json), which is the fallback rung throughout.
 *
 * @group model.classificationstore.label
 */
final class LabelResolverTest extends ModelTestCase
{
    use LabelFixtureTrait;

    private const LANGUAGE_DE = 'de';

    private const LANGUAGE_EN = 'en';

    private const TITLE_DE = 'Höhe';

    private const OWN_TITLE = 'Key title';

    private const OWN_DESCRIPTION = 'Own description';

    private const TITLE_UPDATED = 'Bauhöhe';

    private const TITLE_EN = 'Height';

    private const DESCRIPTION_DE = 'Die Höhe des Artikels';

    private const DESCRIPTION_EN = 'The height of the article';

    /**
     * Store, collection and group have no title column, so their title chain has no title rung.
     */
    private const TITLELESS_TYPES = [
        LabelEntityType::Store,
        LabelEntityType::Collection,
        LabelEntityType::Group,
    ];

    private const ALL_TYPES = [
        LabelEntityType::Store,
        LabelEntityType::Collection,
        LabelEntityType::Group,
        LabelEntityType::Key,
    ];

    protected function tearDown(): void
    {
        $this->deleteLabelFixtures();

        parent::tearDown();
    }

    public function testAKeyTitleWalksTheRequestedLanguageFallbackOwnTitleAndName(): void
    {
        $key = $this->createEntity(LabelEntityType::Key);
        $this->assertInstanceOf(KeyConfig::class, $key);
        $key->setTitle(self::OWN_TITLE);
        $keyId = (int) $key->getId();

        $this->writeLabel(LabelEntityType::Key, $keyId, self::LANGUAGE_DE, self::TITLE_DE, null);
        $this->writeLabel(LabelEntityType::Key, $keyId, self::LANGUAGE_EN, self::TITLE_EN, null);
        $this->assertSame(self::TITLE_DE, $this->resolver()->getTitle($key, self::LANGUAGE_DE));

        $this->removeLabel(LabelEntityType::Key, $keyId, self::LANGUAGE_DE);
        $this->assertSame(self::TITLE_EN, $this->resolver()->getTitle($key, self::LANGUAGE_DE));

        $this->removeLabel(LabelEntityType::Key, $keyId, self::LANGUAGE_EN);
        $this->assertSame(self::OWN_TITLE, $this->resolver()->getTitle($key, self::LANGUAGE_DE));

        // the column is NOT NULL DEFAULT '' while the property is nullable, so both are empty
        $key->setTitle('');
        $this->assertSame($key->getName(), $this->resolver()->getTitle($key, self::LANGUAGE_DE));

        $key->setTitle(self::OWN_TITLE);
        $this->assertSame(self::OWN_TITLE, $this->resolver()->getTitle($key, self::LANGUAGE_DE));
    }

    public function testAKeyWithANullOwnTitleFallsThroughToItsName(): void
    {
        $key = $this->createEntity(LabelEntityType::Key);
        $this->assertInstanceOf(KeyConfig::class, $key);

        $this->assertNull($key->getTitle());
        $this->assertSame($key->getName(), $this->resolver()->getTitle($key, self::LANGUAGE_DE));
    }

    public function testAStoreCollectionAndGroupTitleHasNoOwnTitleRung(): void
    {
        foreach (self::TITLELESS_TYPES as $entityType) {
            $entity = $this->createEntity($entityType);
            $entityId = (int) $entity->getId();
            $because = self::because($entityType);

            $this->writeLabel($entityType, $entityId, self::LANGUAGE_DE, self::TITLE_DE, null);
            $this->writeLabel($entityType, $entityId, self::LANGUAGE_EN, self::TITLE_EN, null);
            $this->assertSame(self::TITLE_DE, $this->resolver()->getTitle($entity, self::LANGUAGE_DE), $because);

            $this->removeLabel($entityType, $entityId, self::LANGUAGE_DE);
            $this->assertSame(self::TITLE_EN, $this->resolver()->getTitle($entity, self::LANGUAGE_DE), $because);

            $this->removeLabel($entityType, $entityId, self::LANGUAGE_EN);
            $this->assertSame($entity->getName(), $this->resolver()->getTitle($entity, self::LANGUAGE_DE), $because);
        }
    }

    public function testTheDescriptionChainEndsAtTheEntityDescriptionAndThenAtNull(): void
    {
        foreach (self::ALL_TYPES as $entityType) {
            $entity = $this->createEntity($entityType);
            $entityId = (int) $entity->getId();
            $because = self::because($entityType);

            $entity->setDescription(self::OWN_DESCRIPTION);

            $this->writeLabel($entityType, $entityId, self::LANGUAGE_DE, null, self::DESCRIPTION_DE);
            $this->writeLabel($entityType, $entityId, self::LANGUAGE_EN, null, self::DESCRIPTION_EN);
            $this->assertSame(
                self::DESCRIPTION_DE,
                $this->resolver()->getDescription($entity, self::LANGUAGE_DE),
                $because
            );

            $this->removeLabel($entityType, $entityId, self::LANGUAGE_DE);
            $this->assertSame(
                self::DESCRIPTION_EN,
                $this->resolver()->getDescription($entity, self::LANGUAGE_DE),
                $because
            );

            $this->removeLabel($entityType, $entityId, self::LANGUAGE_EN);
            $this->assertSame(
                self::OWN_DESCRIPTION,
                $this->resolver()->getDescription($entity, self::LANGUAGE_DE),
                $because
            );
        }
    }

    /**
     * CollectionConfig::$description is a non-nullable string defaulting to '', the other three
     * are nullable, so both forms of empty have to end the chain at null rather than at the name.
     */
    public function testADescriptionNeverFallsThroughToTheName(): void
    {
        foreach (self::ALL_TYPES as $entityType) {
            $entity = $this->createEntity($entityType);
            $because = self::because($entityType);

            // a fresh store, group and key carry a null description, a fresh collection carries ''
            $this->assertNull($this->resolver()->getDescription($entity, self::LANGUAGE_DE), $because);

            $entity->setDescription('');
            $this->assertNull($this->resolver()->getDescription($entity, self::LANGUAGE_DE), $because);

            $this->assertNotSame('', $entity->getName(), $because);
        }
    }

    /**
     * Title and description share one row, so one can be filled without the other: an empty title
     * on a row that carries a description must not stop the title chain there.
     */
    public function testARowWhoseTitleIsEmptyIsTreatedAsAbsent(): void
    {
        $group = $this->createEntity(LabelEntityType::Group);
        $this->assertInstanceOf(GroupConfig::class, $group);
        $groupId = (int) $group->getId();

        $this->writeLabel(LabelEntityType::Group, $groupId, self::LANGUAGE_DE, null, self::DESCRIPTION_DE);
        $this->writeLabel(LabelEntityType::Group, $groupId, self::LANGUAGE_EN, self::TITLE_EN, null);

        $title = $this->resolver()->getTitle($group, self::LANGUAGE_DE);
        $this->assertSame(self::TITLE_EN, $title, 'a NULL title must not stop the chain');
        $this->assertNotSame('', $title);

        $this->writeLabel(LabelEntityType::Group, $groupId, self::LANGUAGE_DE, '', self::DESCRIPTION_DE);
        $this->assertSame(
            self::TITLE_EN,
            $this->resolver()->getTitle($group, self::LANGUAGE_DE),
            'an empty title is absent too'
        );

        // the description of the de row is still the one that answers
        $this->assertSame(self::DESCRIPTION_DE, $this->resolver()->getDescription($group, self::LANGUAGE_DE));
    }

    /**
     * The description chain ends at null, never at a readable value the title rung produced.
     */
    public function testADescriptionChainEndsAtNullRatherThanAtTheTitle(): void
    {
        $store = $this->createEntity(LabelEntityType::Store);
        $this->assertInstanceOf(StoreConfig::class, $store);
        $storeId = (int) $store->getId();

        $this->writeLabel(LabelEntityType::Store, $storeId, self::LANGUAGE_DE, self::TITLE_DE, null);

        $this->assertSame(self::TITLE_DE, $this->resolver()->getTitle($store, self::LANGUAGE_DE));
        $this->assertNull($this->resolver()->getDescription($store, self::LANGUAGE_DE));
    }

    public function testTheResolverIsResettableAndSeesALabelWrittenInTheSameRequest(): void
    {
        $resolver = $this->resolver();
        $this->assertInstanceOf(ResetInterface::class, $resolver, 'the kernel.reset tag needs reset()');

        $collection = $this->createEntity(LabelEntityType::Collection);
        $collectionId = (int) $collection->getId();

        $this->writeLabel(LabelEntityType::Collection, $collectionId, self::LANGUAGE_DE, self::TITLE_DE, null);
        $this->assertSame(self::TITLE_DE, $resolver->getTitle($collection, self::LANGUAGE_DE));

        $this->writeLabel(LabelEntityType::Collection, $collectionId, self::LANGUAGE_DE, self::TITLE_UPDATED, null);
        $this->assertSame(
            self::TITLE_UPDATED,
            $resolver->getTitle($collection, self::LANGUAGE_DE),
            'a write invalidates the held row'
        );

        $resolver->reset();
        $this->assertSame(self::TITLE_UPDATED, $resolver->getTitle($collection, self::LANGUAGE_DE));
    }

    private static function because(LabelEntityType $entityType): string
    {
        return 'of a ' . $entityType->value;
    }

    private function resolver(): LabelResolver
    {
        return Pimcore::getContainer()->get(LabelResolver::class);
    }

    private function removeLabel(LabelEntityType $entityType, int $entityId, string $language): void
    {
        Label::getByEntity($entityType, $entityId, $language)?->delete();
    }
}
