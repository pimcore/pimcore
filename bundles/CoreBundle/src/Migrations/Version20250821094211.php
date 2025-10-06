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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\ClassDefinition\Listing;

/**
 * Sets sourceSite=0 (Main domain) for all redirects with sourceSite = NULL
 * before NULL and 0 were both treated as main domain and in fact sourceSite was not optional (although UI told so)
 */
final class Version20250821094211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set column type of "auto_id" for object_metadata_* tables to BIGINT';
    }

    public function up(Schema $schema): void
    {
        $classes = new Listing();
        foreach($classes->getClasses() as $class) {
            if($schema->hasTable('object_metadata_' . $class->getId())) {
                $this->addSql(
                    'ALTER TABLE object_metadata_' . $class->getId() . ' CHANGE auto_id auto_id BIGINT(20) NOT NULL'
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $classes = new Listing();
        foreach ($classes->getClasses() as $class) {
            if ($schema->hasTable('object_metadata_'.$class->getId())) {
                $this->addSql(
                    'ALTER TABLE object_metadata_'.$class->getId().' CHANGE auto_id auto_id INT(11) NOT NULL'
                );
            }
        }
    }
}
