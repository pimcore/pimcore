<?php


declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\ClassDefinition\Dbal;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Model\DataObject\ClassDefinition;

class SchemaBuilderLocalized extends AbstractSchemaBuilder implements SchemaBuilderLocalizedInterface
{
    public function buildSchema(ClassDefinition $classDefinition, array $context, string $locale): Schema
    {
        $tableName = 'object_localized_data_'.$classDefinition->getId();

        if ($context) {
            $containerType = $context['containerType'] ?? null;
            if ($containerType === 'fieldcollection') {
                $containerKey = $context['containerKey'];

                return 'object_collection_'.$containerKey.'_localized_'.$classDefinition->getId();
            } elseif ($containerType === 'objectbrick') {
                $containerKey = $context['containerKey'];

                return 'object_brick_localized_'.$containerKey.'_'.$classDefinition->getId();
            }
        }


    }
}
