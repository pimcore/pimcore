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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug;
use Pimcore\Model\DataObject\Data\UrlSlug as UrlSlugData;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * URL slugs of localized fields in object bricks written before the brick type was part of the owner name
 * (`/objectbrick~<field>//localizedfield~localizedfield`) are read per brick now and would no longer be found.
 * Version20230320131322 repaired the relations only.
 */
final class Version20261007120000 extends AbstractMigration
{
    private const LEGACY_OWNERNAME = '#^/objectbrick~([^/]+)//localizedfield~localizedfield$#u';

    public function getDescription(): string
    {
        return 'Add the brick type to the owner name of URL slugs of localized fields in object bricks';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(UrlSlugData::TABLE_NAME)) {
            return;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT slug, siteId, objectId, classId, fieldname, ownername FROM ' . UrlSlugData::TABLE_NAME
            . " WHERE ownertype = 'localizedfield' AND ownername LIKE '/objectbrick~%//localizedfield~localizedfield'"
        );

        foreach ($rows as $row) {
            if (!preg_match(self::LEGACY_OWNERNAME, $row['ownername'], $match)) {
                continue;
            }

            $brickType = $this->resolveBrickType($row['classId'], $match[1], $row['fieldname'], (int) $row['objectId']);
            if ($brickType === null) {
                $this->write(sprintf(
                    'URL slug "%s" of object %d: the brick of field "%s" is ambiguous or gone, left unchanged.',
                    $row['slug'],
                    $row['objectId'],
                    $row['fieldname']
                ));

                continue;
            }

            $this->connection->update(
                UrlSlugData::TABLE_NAME,
                ['ownername' => '/objectbrick~' . $match[1] . '/' . $brickType . '/localizedfield~localizedfield'],
                ['slug' => $row['slug'], 'siteId' => $row['siteId']]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('Nothing to revert: owner names with the brick type are also read by earlier versions.');
    }

    /**
     * The brick type, if exactly one brick of the object has a localized URL slug field of that name.
     */
    private function resolveBrickType(string $classId, string $containerField, string $fieldname, int $objectId): ?string
    {
        $class = ClassDefinition::getById($classId);
        $containerDefinition = $class?->getFieldDefinition($containerField);
        if (!$containerDefinition instanceof Objectbricks) {
            return null;
        }

        $candidates = [];
        foreach ($containerDefinition->getAllowedTypes() as $type) {
            $brickDefinition = Definition::getByKey($type);
            $localizedFields = $brickDefinition?->getFieldDefinition('localizedfields');
            if (!$localizedFields instanceof Localizedfields
                || !$localizedFields->getFieldDefinition($fieldname) instanceof UrlSlug
            ) {
                continue;
            }

            try {
                $hasBrick = (bool) $this->connection->fetchOne(
                    'SELECT 1 FROM `' . $brickDefinition->getTableName($class, false) . '` WHERE id = ? AND fieldname = ? LIMIT 1',
                    [$objectId, $containerField]
                );
            } catch (TableNotFoundException) {
                $hasBrick = false;
            }

            if ($hasBrick) {
                $candidates[] = $type;
            }
        }

        return count($candidates) === 1 ? $candidates[0] : null;
    }
}
