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

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;

/**
 * Answers which languages a classification store key is kept in.
 *
 * A key decides for itself through {@see KeyConfig::getLocalized()}, and a key that does not - the
 * state every key keeps until someone sets the flag - inherits the `localized` flag of the class
 * definition field it is rendered in. The same key can therefore be localized in one class and not
 * in another, which is what the field flag already did for a whole store.
 *
 * This is the only place that reads the deprecated field flag, so the paths enumerating languages
 * ask the key rather than the field.
 *
 * @internal
 */
final class LocalizationResolver
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * Whether the key is kept per language in this field. A key id that resolves to no
     * configuration - a key deleted while a value of it is still stored - falls back to the field,
     * which is what the whole store did before the flag existed.
     */
    public function isKeyLocalized(int $keyId, ClassificationstoreField $definition): bool
    {
        return DefinitionCache::get($keyId)?->getLocalized() ?? $definition->isLocalized();
    }

    /**
     * Whether any key of the field can hold a language other than `default`: the field's own flag,
     * which every key without one inherits, or a key of its store that is flagged localized.
     *
     * A field definition that carries no store id holds no key either, so it answers false - the
     * property is typed without a default, which is why the store id is read through
     * {@see Service::getConfiguredStoreId()} rather than through the getter.
     */
    public function hasLocalizedKey(ClassificationstoreField $definition): bool
    {
        if ($definition->isLocalized()) {
            return true;
        }

        $storeId = Service::getConfiguredStoreId($definition);
        if ($storeId === null) {
            return false;
        }

        return (bool) $this->db->fetchOne(
            'SELECT EXISTS(SELECT 1 FROM ' . KeyConfig\Dao::TABLE_NAME_KEYS
            . ' WHERE storeId = :storeId AND localized = 1)',
            ['storeId' => $storeId]
        );
    }
}
