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

/**
 * The supported way to read the title a classification store entity is shown under, for the
 * packages that render one. The implementation behind it stays internal; this is the contract.
 *
 * Both lookups walk the same chain - the requested language, the languages it falls back to
 * transitively, the installation's default language, and then the entity's own title, which only
 * a key carries. They differ only in what happens when that chain produces nothing:
 * {@see self::getTitle()} falls through to the entity's technical name, so an editor never renders
 * an empty slot, while {@see self::getStoredTitle()} returns null, so a payload that must stay
 * empty when nothing is stored does not publish that name.
 */
interface LabelResolverInterface
{
    /**
     * Reads the labels of a set of entities of one type with a single statement, so that resolving
     * them afterwards costs nothing.
     *
     * @param int[] $entityIds
     */
    public function preload(LabelEntityType $entityType, array $entityIds, string $language): void;

    /**
     * The title to show, which is the entity's name when nothing else answers.
     */
    public function getTitle(
        StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity,
        string $language
    ): ?string;

    /**
     * The title that is actually stored for the entity, or null - never its name.
     */
    public function getStoredTitle(
        StoreConfig|CollectionConfig|GroupConfig|KeyConfig $entity,
        string $language
    ): ?string;
}
