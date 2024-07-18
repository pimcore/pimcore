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

namespace Pimcore\Model\DataObject\Traits;

use Exception;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;

/**
 * @internal
 */
trait ContextPersistenceTrait
{
    protected function prepareMyCurrentRelations(
        Localizedfield|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object,
        array $params
    ): array {
        if ($object instanceof Concrete) {
            $relations = $object->retrieveRelationData(['fieldname' => $this->getName(), 'ownertype' => 'object']);
        } elseif ($object instanceof AbstractData) {
            $relations = $object->getObject()->retrieveRelationData(
                [
                    'fieldname' => $this->getName(),
                    'ownertype' => 'fieldcollection',
                    'ownername' => $object->getFieldname(),
                    'position' => (string)$object->getIndex(), //Gets cast to string for checking a delta of the relations on removal or addition
                ]
            );
        } elseif ($object instanceof Localizedfield) {
            $context = $params['context'] ?? null;
            if (isset($context['containerType']) &&
                ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
                $fieldname = $context['fieldname'] ?? null;
                if ($context['containerType'] === 'fieldcollection') {
                    $index = $context['index'] ?? null;
                    $filter = '/'.$context['containerType'].'~'.$fieldname.'/'.$index.'/%';
                } else {
                    $filter = '/'.$context['containerType'].'~'.$fieldname.'/%';
                }
                $relations = $object->getObject()->retrieveRelationData(
                    [
                        'fieldname' => $this->getName(),
                        'ownertype' => 'localizedfield',
                        'ownername' => $filter,
                        'position' => $params['language'],
                    ]
                );
            } else {
                $relations = $object->getObject()->retrieveRelationData(
                    [
                        'fieldname' => $this->getName(),
                        'ownertype' => 'localizedfield',
                        'position' => $params['language'],
                    ]
                );
            }
        } elseif ($object instanceof \Pimcore\Model\DataObject\Objectbrick\Data\AbstractData) {
            $relations = $object->getObject()->retrieveRelationData(
                [
                    'fieldname' => $this->getName(),
                    'ownertype' => 'objectbrick',
                    'ownername' => $object->getFieldname(),
                    'position' => $object->getType(),
                ]
            );
        } else {
            throw new Exception('Invalid object type');
        }

        return $relations;
    }

    /**
     * Enrich relation / slug with type-specific data.
     *
     */
    protected function enrichDataRow(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params, ?string &$classId, array &$row = [], string $srcCol = 'src_id'): void
    {
        if (!$row) {
            $row = [];
        }

        if ($object instanceof Concrete) {
            $row[$srcCol] = $object->getId();
            $row['ownertype'] = 'object';

            $classId = $object->getClassId();
        } elseif ($object instanceof AbstractData) {
            $row[$srcCol] = $object->getObject()->getId(); // use the id from the object, not from the field collection
            $row['ownertype'] = 'fieldcollection';
            $row['ownername'] = $object->getFieldname();
            $row['position'] = (string)$object->getIndex();

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof Localizedfield) {
            $row[$srcCol] = $object->getObject()->getId();
            $row['ownertype'] = 'localizedfield';
            $row['ownername'] = 'localizedfield';
            $context = $object->getContext();
            if (isset($context['containerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
                $fieldname = $context['fieldname'];
                $index = $context['index'] ?? $context['containerKey'] ?? null;
                $row['ownername'] = '/' . $context['containerType'] . '~' . $fieldname . '/' . $index . '/localizedfield~' . $row['ownername'];
            }

            $row['position'] = $params['language'];

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof \Pimcore\Model\DataObject\Objectbrick\Data\AbstractData) {
            $row[$srcCol] = $object->getObject()->getId();
            $row['ownertype'] = 'objectbrick';
            $row['ownername'] = $object->getFieldname();
            $row['position'] = $object->getType();

            $classId = $object->getObject()->getClassId();
        }
    }
}
