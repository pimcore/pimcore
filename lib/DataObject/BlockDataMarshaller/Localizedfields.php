<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\BlockDataMarshaller;

use Pimcore\Element\MarshallerService;
use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class Localizedfields implements MarshallerInterface
{
    /**
     * @var MarshallerService
     */
    protected $marshallerService;

    /**
     * Localizedfields constructor.
     *
     * @param MarshallerService $marshallerService
     */
    public function __construct(MarshallerService $marshallerService)
    {
        $this->marshallerService = $marshallerService;
    }

    /**
     * {@inheritdoc}
     */
    public function marshal($value, $params = [])
    {
        $object = $params['object'] ?? null;
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
        $fieldDefinition = $params['fieldDefinition'];
        $childDefs = $fieldDefinition->getFieldDefinitions();
        $result = [];

        if (is_array($value)) {
            foreach ($value as $language => $items) {
                $result[$language] = [];
                foreach ($items as $key => $normalizedData) {
                    $childDef = $childDefs[$key];

                    if ($this->marshallerService->supportsFielddefinition('block', $childDef->getFieldtype())) {
                        $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $childDef->getFieldtype());
                        // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                        $encodedData = $marshaller->marshal($normalizedData, ['object' => $object, 'fieldDefinition' => $childDef, 'format' => 'block']);
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

    /**
     * {@inheritdoc}
     */
    public function unmarshal($value, $params = [])
    {
        $object = $params['object'] ?? null;
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
        $fieldDefinition = $params['fieldDefinition'];
        $childDefs = $fieldDefinition->getFieldDefinitions();
        $result = [];

        if (is_array($value)) {
            foreach ($value as $language => $items) {
                $result[$language] = [];
                foreach ($items as $key => $normalizedData) {
                    $childDef = $childDefs[$key];

                    if ($this->marshallerService->supportsFielddefinition('block', $childDef->getFieldtype())) {
                        $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $childDef->getFieldtype());
                        // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                        $decodedData = $marshaller->unmarshal($normalizedData, ['object' => $object, 'fieldDefinition' => $childDef, 'format' => 'block']);
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
