<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Cache;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

final class Version20230320131322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair localized relations in object bricks';
    }

    public function up(Schema $schema): void
    {
        $classDefinitionListing = new Listing();
        foreach ($classDefinitionListing->getClasses() as $classDefinition) {
            $relations = Db::get()->fetchAllAssociative('SELECT src_id, ownername, fieldname, position FROM object_relations_'.$classDefinition->getId().' WHERE ownertype=\'localizedfield\' AND ownername LIKE \'/objectbrick~%\'');
            foreach ($relations as $relationItem) {
                if (preg_match('/^\/objectbrick~([^\/]+)/', $relationItem['ownername'], $match)) {
                    $object = Concrete::getById($relationItem['src_id']);
                    if (!$object instanceof Concrete) {
                        continue;
                    }
                    $objectBrickContainerField = $match[1];
                    $brickGetter = 'get'.$objectBrickContainerField;

                    /** @var \Pimcore\Model\DataObject\Objectbrick $brickContainer */
                    $brickContainer = $object->$brickGetter();

                    /** @var AbstractData $objectBrick */
                    foreach ($brickContainer->getItems() as $objectBrick) {
                        $brickDefinition = $objectBrick->getDefinition();
                        $localizedFieldDefinition = $brickDefinition->getFieldDefinition('localizedfields');
                        if ($localizedFieldDefinition instanceof Localizedfields) {
                            if ($localizedFieldDefinition->getFieldDefinition($relationItem['fieldname'])) {
                                $fieldGetter = 'get'.$relationItem['fieldname'];
                                $fieldSetter = 'set'.$relationItem['fieldname'];
                                $objectBrick->$fieldSetter($objectBrick->$fieldGetter($relationItem['position']), $relationItem['position']);
                                $objectBrick->markFieldDirty('localizedfields');
                                $objectBrick->markFieldDirty($relationItem['fieldname']);
                                if (!method_exists($objectBrick, 'getLocalizedfields')) {
                                    // this cannot happen, because we already checked that there are localized fields via $brickDefinition->getFieldDefinition('localizedfields') but PhpStan complains...
                                    continue;
                                }
                                /** @var Localizedfield $localizedFields */
                                $localizedFields = $objectBrick->getLocalizedfields();
                                $localizedFields->markLanguageAsDirty($relationItem['position']);
                                $localizedFields->markFieldDirty($relationItem['fieldname']);

                                Db::get()->executeStatement('DELETE FROM object_relations_'.$classDefinition->getId().' WHERE src_id=? AND fieldname=? AND ownertype=\'localizedfield\' AND ownername LIKE \'/objectbrick~%\'', [$object->getId(), $relationItem['fieldname']]);

                                $objectBrick->save($object, [
                                    'isUntouchable' => false,
                                    'isUpdate' => true,
                                    'context' => [
                                        'containerType' => 'object',
                                    ],
                                    'owner' => $object,
                                    'fieldname' => $objectBrickContainerField,
                                    'saveRelationalData' => ['saveLocalizedRelations' => true],
                                ]);

                                continue 2;
                            }
                        }
                    }

                    Cache::remove('object_'.$object->getId());
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->up($schema);
    }
}
