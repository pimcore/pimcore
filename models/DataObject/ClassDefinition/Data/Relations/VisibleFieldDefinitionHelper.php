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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Exception;
use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * Shared building blocks for the "visible fields" feature of relation types: resolving the
 * class a relation is restricted to, looking up a (possibly localized) field on it and turning
 * a field definition into the array shape the UI layers expect in `visibleFieldDefinitions`.
 *
 * @internal
 */
final class VisibleFieldDefinitionHelper
{
    private function __construct()
    {
    }

    /**
     * Resolves a class by id or by name, as stored in the `classes` / `allowedClassId`
     * configuration of relation types.
     *
     * @throws Exception
     */
    public static function resolveClass(int|string|null $classIdentifier): ?ClassDefinition
    {
        if ($classIdentifier === null || $classIdentifier === '') {
            return null;
        }

        if (is_numeric($classIdentifier)) {
            return ClassDefinition::getById((string) $classIdentifier);
        }

        return ClassDefinition::getByName((string) $classIdentifier);
    }

    /**
     * Looks a field up on a class; ClassDefinition::getFieldDefinition() already falls back to the
     * class' localized fields, so a localized field name resolves as well.
     */
    public static function findClassFieldDefinition(ClassDefinition $class, string $name, array $context = []): ?Data
    {
        return $class->getFieldDefinition($name, $context);
    }

    /**
     * Describes a class field as a read-only visible field.
     *
     * @return array<string, mixed>
     */
    public static function buildDefinition(Data $fieldDefinition): array
    {
        $definition = [
            'name' => $fieldDefinition->getName(),
            'title' => $fieldDefinition->getTitle(),
            'fieldtype' => $fieldDefinition->getFieldType(),
            'noteditable' => true,
        ];

        if ($fieldDefinition instanceof Data\Select || $fieldDefinition instanceof Data\Multiselect) {
            $definition['optionsProviderClass'] = $fieldDefinition->getOptionsProviderClass();
            $definition['options'] = $fieldDefinition->getOptions();
        } elseif ($fieldDefinition instanceof Data\BooleanSelect) {
            $definition['options'] = $fieldDefinition->getOptions();
        }

        return $definition;
    }

    /**
     * Describes a visible field that does not (or no longer) resolve to a known field, so the UI
     * can still render a plain read-only column for it.
     *
     * @return array<string, mixed>
     */
    public static function buildFallbackDefinition(string $name): array
    {
        return [
            'name' => $name,
            'title' => Pimcore::getContainer()->get('translator')->trans($name, [], 'admin'),
            'fieldtype' => 'input',
            'noteditable' => true,
        ];
    }
}
