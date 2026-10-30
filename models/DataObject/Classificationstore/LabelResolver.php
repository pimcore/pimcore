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
 * The title chain is the requested language, then the languages it falls back to transitively, then
 * the default language, then - for a key only, because no other entity carries one - the key's own
 * title, and finally the entity's name. {@see self::getStoredTitle()} walks the same chain but ends
 * one rung earlier, at null, for a caller that must not publish the technical name. The description
 * chain is the same set of languages, then the entity's own description, and then nothing: a
 * description never falls through to the name, so it needs no such twin.
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
final class LabelResolver implements LabelResolverInterface, ResetInterface
{
    /**
     * @var array<string, array<int, array<string, Label|null>>> entity type => entity id => language => label
     */
    private array $labels = [];

    public function getTitle(
        StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity,
        string $language
    ): ?string {
        return $this->getStoredTitle($entity, $language) ?? $entity->getName();
    }

    public function getStoredTitle(
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
     * them afterwards costs nothing. The whole language chain of the requested language is read
     * with it, so a longer chain costs no additional statement.
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
     * Walks the language chain of the requested language, skipping a row that carries no usable
     * value for the field being read.
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

        $list = new Label\Listing();
        $list->setCondition(
            '`entityType` = ? AND `entityId` IN (' . implode(',', array_fill(0, count($missing), '?')) . ')'
            . ' AND `language` IN (' . implode(',', array_fill(0, count($languages), '?')) . ')',
            [$entityType->value, ...$missing, ...$languages]
        );

        // held only once the load succeeded, so a failed read is not remembered as a missing label
        $loaded = $list->load();

        foreach ($missing as $entityId) {
            foreach ($languages as $chainLanguage) {
                $this->labels[$entityType->value][$entityId][$chainLanguage] ??= null;
            }
        }

        foreach ($loaded as $label) {
            $this->labels[$entityType->value][$label->getEntityId()][$label->getLanguage()] = $label;
        }
    }

    /**
     * The requested language, then the languages configured as its fallbacks - transitively, as a
     * fallback language may configure fallbacks of its own - and last the default language, which
     * is the rung that answers the ordinary installation: fallback_languages is empty until an
     * operator fills it in, and without this step every such installation falls through to the
     * technical name.
     *
     * The chain doubles as the visited set, so a cycle among the configured fallbacks ends here
     * rather than looping.
     *
     * @return string[]
     */
    private function languageChain(string $language): array
    {
        $chain = [];
        $pending = [$language];

        while ($pending !== []) {
            $current = array_shift($pending);
            if (in_array($current, $chain, true)) {
                continue;
            }

            $chain[] = $current;
            // breadth first, like the fallback walk of Classificationstore::getFallbackValue()
            foreach (Tool::getFallbackLanguagesFor($current) as $fallbackLanguage) {
                $pending[] = $fallbackLanguage;
            }
        }

        $defaultLanguage = Tool::getDefaultLanguage();
        if ($defaultLanguage !== null && !in_array($defaultLanguage, $chain, true)) {
            $chain[] = $defaultLanguage;
        }

        return $chain;
    }

    /**
     * The entity's own title column, which only a key carries.
     */
    private function ownTitle(StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity): ?string
    {
        if (!$entity instanceof KeyConfig) {
            return null;
        }

        // the column is NOT NULL DEFAULT '' while the property is nullable, so both are empty
        $title = $entity->getTitle();

        return $title === '' ? null : $title;
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
