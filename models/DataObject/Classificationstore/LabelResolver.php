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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Tool;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Resolves the title and the description a classification store entity is shown under.
 *
 * The title chain is the requested language, then the configured fallback languages, then - for a
 * key only, because no other entity carries one - the key's own title, and finally the entity's
 * name. The description chain is the requested language, then the fallbacks, then the entity's own
 * description, and then nothing: a description never falls through to the name.
 *
 * A stored title or description that is NULL or empty counts as absent, so the chain continues
 * past it; the two share a row, so one can be filled without the other.
 *
 * The rows are held per request, keyed by (entityType, entityId, language), and read in batches:
 * {@see self::preload()} warms a whole set of entities with a single statement, which the primary
 * key of the label table serves as a prefix lookup. The memo is cleared at the request and
 * messenger-message boundary via the kernel.reset tag (ResetInterface), and on a label write by
 * {@see Label\Dao}, which dispatches no event of its own.
 *
 * @internal
 */
final class LabelResolver implements ResetInterface
{
    /**
     * @var array<string, array<int, array<string, Label|null>>> entity type => entity id => language => label
     */
    private array $labels = [];

    public function getTitle(
        StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity,
        string $language
    ): ?string {
        $entityId = $entity->getId();
        if ($entityId === null) {
            return $this->ownTitle($entity);
        }

        $title = $this->walk(
            $this->entityTypeOf($entity),
            $entityId,
            $language,
            static fn (Label $label): ?string => $label->getTitle()
        );

        return $title ?? $this->ownTitle($entity);
    }

    public function getDescription(
        StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity,
        string $language
    ): ?string {
        $entityId = $entity->getId();
        if ($entityId !== null) {
            $description = $this->walk(
                $this->entityTypeOf($entity),
                $entityId,
                $language,
                static fn (Label $label): ?string => $label->getDescription()
            );

            if ($description !== null) {
                return $description;
            }
        }

        $own = $entity->getDescription();

        return $own === '' ? null : $own;
    }

    /**
     * Reads the labels of a set of entities of one type with a single statement, so that resolving
     * them afterwards costs nothing. The fallback languages of the requested one are read with it.
     *
     * @param int[] $entityIds
     */
    public function preload(LabelEntityType $entityType, array $entityIds, string $language): void
    {
        $this->loadChain($entityType, $entityIds, $this->languageChain($language));
    }

    /**
     * Drops the memo of one entity, for the code that has just written one of its labels.
     */
    public function invalidate(LabelEntityType $entityType, int $entityId): void
    {
        unset($this->labels[$entityType->value][$entityId]);
    }

    public function reset(): void
    {
        $this->labels = [];
    }

    /**
     * Walks the requested language and then its configured fallbacks, skipping a row that carries
     * no usable value for the field being read.
     *
     * @param callable(Label): ?string $read
     */
    private function walk(
        LabelEntityType $entityType,
        int $entityId,
        string $language,
        callable $read
    ): ?string {
        $languages = $this->languageChain($language);
        $this->loadChain($entityType, [$entityId], $languages);

        foreach ($languages as $chainLanguage) {
            $label = $this->labels[$entityType->value][$entityId][$chainLanguage] ?? null;
            $value = $label === null ? null : $read($label);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Reads every (entity, language) pair of the chain that is not held yet with one statement.
     * An entity without a row for a language is held as null, so it is not looked up again.
     *
     * @param int[] $entityIds
     * @param string[] $languages
     */
    private function loadChain(LabelEntityType $entityType, array $entityIds, array $languages): void
    {
        $missing = [];

        foreach (array_unique($entityIds) as $entityId) {
            foreach ($languages as $chainLanguage) {
                if (!array_key_exists($chainLanguage, $this->labels[$entityType->value][$entityId] ?? [])) {
                    $missing[] = $entityId;

                    break;
                }
            }
        }

        if ($missing === []) {
            return;
        }

        foreach ($missing as $entityId) {
            foreach ($languages as $chainLanguage) {
                $this->labels[$entityType->value][$entityId][$chainLanguage] ??= null;
            }
        }

        $list = new Label\Listing();
        $list->setCondition(
            '`entityType` = ? AND `entityId` IN (' . implode(',', array_fill(0, count($missing), '?')) . ')'
            . ' AND `language` IN (' . implode(',', array_fill(0, count($languages), '?')) . ')',
            [$entityType->value, ...$missing, ...$languages]
        );

        foreach ($list->load() as $label) {
            $this->labels[$entityType->value][$label->getEntityId()][$label->getLanguage()] = $label;
        }
    }

    /**
     * @return string[] the requested language followed by the fallbacks configured for it
     */
    private function languageChain(string $language): array
    {
        return array_values(array_unique([$language, ...Tool::getFallbackLanguagesFor($language)]));
    }

    private function ownTitle(StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity): ?string
    {
        if ($entity instanceof KeyConfig) {
            // the column is NOT NULL DEFAULT '' while the property is nullable, so both are empty
            $title = $entity->getTitle();
            if ($title !== null && $title !== '') {
                return $title;
            }
        }

        return $entity->getName();
    }

    private function entityTypeOf(StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity): LabelEntityType
    {
        return match (true) {
            $entity instanceof StoreConfig => LabelEntityType::Store,
            $entity instanceof CollectionConfig => LabelEntityType::Collection,
            $entity instanceof GroupConfig => LabelEntityType::Group,
            $entity instanceof KeyConfig => LabelEntityType::Key,
        };
    }
}
