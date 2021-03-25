<?php
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

namespace Pimcore\DataObject\FielddefinitionMarshaller\Block;

use Pimcore\DataObject\MarshallerInterface;
use Pimcore\DataObject\MarshallerService;

class Localizedfields implements MarshallerInterface
{
    /** @inheritDoc */
    public function marshal($value, $params = [])
    {
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
        $fieldDefinition =$params['fieldDefinition'];
        $childDefs = $fieldDefinition->getFieldDefinitions();
        $result = [];

        if (is_array($value)) {
            foreach ($value as $language => $items) {
                $result[$language] = [];
                foreach ($items as $key => $normalizedData) {
                    $childDef = $childDefs[$key];

                    /** @var MarshallerService $marshallerService */
                    $marshallerService = \Pimcore::getContainer()->get(MarshallerService::class);

                    if ($marshallerService->supportsFielddefinition('block', $childDef->getFieldtype())) {
                        $marshaller = $marshallerService->buildFieldefinitionMarshaller('block', $childDef->getFieldtype());
                        // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                        $encodedData = $marshaller->marshal($normalizedData, ['object' => $object, 'fieldDefinition' => $fd, 'format' => 'block']);
                    } else {
                        $encodedData = $normalizedData;
                    }
                    $result[$language][$key] = $encodedData;
                }
            }
            return $result;
        }

        return null;
    }

    /** @inheritDoc */
    public function unmarshal($value, $params = [])
    {
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
        $fieldDefinition =$params['fieldDefinition'];
        $childDefs = $fieldDefinition->getFieldDefinitions();
        $result = [];

        if (is_array($value)) {
            foreach ($value as $language => $items) {
                $result[$language] = [];
                foreach ($items as $key => $normalizedData) {
                    $childDef = $childDefs[$key];

                    /** @var MarshallerService $marshallerService */
                    $marshallerService = \Pimcore::getContainer()->get(MarshallerService::class);

                    if ($marshallerService->supportsFielddefinition('block', $childDef->getFieldtype())) {
                        $marshaller = $marshallerService->buildFieldefinitionMarshaller('block', $childDef->getFieldtype());
                        // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                        $decodedData = $marshaller->unmarshal($normalizedData, ['object' => $object, 'fieldDefinition' => $fd, 'format' => 'block']);
                    } else {
                        $decodedData = $normalizedData;
                    }
                    $result[$language][$key] = $decodedData;
                }
            }
            return $result;
        }

        return null;
    }


}
